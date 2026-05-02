#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
工厂批量导入工具
基于 factory_api.php 接口，批量导入产品信息
"""

import json
import os
import sys
import configparser
import logging
from datetime import datetime
from threading import Thread

import requests

# 尝试导入 tkinter
try:
    import tkinter as tk
    from tkinter import ttk, messagebox, scrolledtext
except ImportError:
    print("错误：需要 tkinter 支持，请安装 python3-tk 或使用包含 tkinter 的 Python 版本。")
    sys.exit(1)

# ==================== 配置文件路径 ====================
APP_DIR = os.path.dirname(os.path.abspath(sys.argv[0]))
CONFIG_FILE = os.path.join(APP_DIR, "factory_import_template.ini")
LOG_FILE = os.path.join(APP_DIR, "factory_import.log")
LAST_CONFIG_FILE = os.path.join(APP_DIR, "factory_import_last.ini")

# ==================== 日志配置 ====================
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s | %(message)s",
    handlers=[
        logging.FileHandler(LOG_FILE, encoding="utf-8"),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger("factory_import")


# ==================== 应用主类 ====================
class FactoryImportApp:
    def __init__(self, root):
        self.root = root
        self.root.title("工厂批量导入工具 v1.0")
        self.root.geometry("1050x720")
        self.root.minsize(950, 650)

        # 绑定关闭事件
        self.root.protocol("WM_DELETE_WINDOW", self.on_close)

        # 缓存请求 session
        self.session = requests.Session()
        self.session.headers.update({"Content-Type": "application/json"})

        # 统计
        self.total_count = 0
        self.success_count = 0
        self.fail_count = 0

        # 构建界面
        self.build_ui()

        # 自动加载上次配置
        self.auto_load_last_config()

    # ==================== UI 构建 ====================
    def build_ui(self):
        # 主容器：左右分栏
        main_panel = ttk.PanedWindow(self.root, orient=tk.HORIZONTAL)
        main_panel.pack(fill=tk.BOTH, expand=True, padx=8, pady=8)

        # ---- 右侧：输入面板 ----
        right_frame = ttk.Frame(main_panel, width=520)
        main_panel.add(right_frame, weight=1)

        # 滚动区域包裹输入面板
        canvas = tk.Canvas(right_frame, highlightthickness=0)
        scrollbar = ttk.Scrollbar(right_frame, orient=tk.VERTICAL, command=canvas.yview)
        self.input_scroll_frame = ttk.Frame(canvas)

        self.input_scroll_frame.bind(
            "<Configure>",
            lambda e: canvas.configure(scrollregion=canvas.bbox("all"))
        )

        canvas.create_window((0, 0), window=self.input_scroll_frame, anchor="nw", tags="input_inner")
        canvas.configure(yscrollcommand=scrollbar.set)

        # 让内部 frame 宽度跟随 canvas
        def _on_canvas_configure(event):
            canvas.itemconfig("input_inner", width=event.width)
        canvas.bind("<Configure>", _on_canvas_configure)

        canvas.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)
        scrollbar.pack(side=tk.RIGHT, fill=tk.Y)

        # 鼠标滚轮支持
        def _on_mousewheel(event):
            canvas.yview_scroll(int(-1 * (event.delta / 120)), "units")
        canvas.bind_all("<MouseWheel>", _on_mousewheel)

        self.build_right_panel(self.input_scroll_frame)

        # ---- 左侧：日志输出面板（只读） ----
        left_frame = ttk.Frame(main_panel, width=480)
        main_panel.add(left_frame, weight=1)

        ttk.Label(left_frame, text="请求日志（只读输出）", font=("Microsoft YaHei", 10, "bold")).pack(anchor=tk.W, pady=(0, 4))

        self.log_text = scrolledtext.ScrolledText(
            left_frame,
            wrap=tk.WORD,
            state=tk.DISABLED,
            font=("Consolas", 9),
            bg="#1e1e1e",
            fg="#d4d4d4",
            insertbackground="white"
        )
        self.log_text.pack(fill=tk.BOTH, expand=True)

        # 底部统计栏
        stats_frame = ttk.Frame(left_frame)
        stats_frame.pack(fill=tk.X, pady=(4, 0))
        self.stats_label = ttk.Label(stats_frame, text="总计: 0  |  成功: 0  |  失败: 0", font=("Microsoft YaHei", 9))
        self.stats_label.pack(side=tk.LEFT)

        # 清空日志按钮
        ttk.Button(stats_frame, text="清空日志", command=self.clear_log).pack(side=tk.RIGHT)

    def build_right_panel(self, parent):
        """构建右侧输入面板"""
        row_idx = 0

        # ===== 服务器配置区域 =====
        ttk.Label(parent, text="服务器配置", font=("Microsoft YaHei", 10, "bold")).grid(
            row=row_idx, column=0, columnspan=2, sticky=tk.W, pady=(4, 2))
        row_idx += 1

        # 调用地址
        ttk.Label(parent, text="调用地址:", width=12).grid(row=row_idx, column=0, sticky=tk.E, padx=(0, 4), pady=2)
        self.api_url_var = tk.StringVar(value="https://")
        self.api_url_entry = ttk.Entry(parent, textvariable=self.api_url_var, width=52)
        self.api_url_entry.grid(row=row_idx, column=1, sticky=tk.EW, pady=2)
        row_idx += 1

        # 调用口令
        ttk.Label(parent, text="调用口令:", width=12).grid(row=row_idx, column=0, sticky=tk.E, padx=(0, 4), pady=2)
        self.token_var = tk.StringVar()
        self.token_entry = ttk.Entry(parent, textvariable=self.token_var, width=52, show="*")
        self.token_entry.grid(row=row_idx, column=1, sticky=tk.EW, pady=2)
        # 显示/隐藏口令
        self.show_token_var = tk.BooleanVar(value=False)
        ttk.Checkbutton(parent, text="显示", variable=self.show_token_var,
                        command=self.toggle_token_visibility).grid(
            row=row_idx, column=1, sticky=tk.E, padx=(0, 6))
        row_idx += 1

        # ===== 模板操作按钮 =====
        ttk.Separator(parent, orient=tk.HORIZONTAL).grid(
            row=row_idx, column=0, columnspan=2, sticky=tk.EW, pady=6)
        row_idx += 1

        btn_frame = ttk.Frame(parent)
        btn_frame.grid(row=row_idx, column=0, columnspan=2, sticky=tk.EW, pady=(0, 6))
        ttk.Button(btn_frame, text="保存模板", command=self.save_template).pack(side=tk.LEFT, padx=(0, 6))
        ttk.Button(btn_frame, text="导入模板", command=self.load_template).pack(side=tk.LEFT, padx=(0, 6))
        ttk.Button(btn_frame, text="恢复上次配置", command=self.auto_load_last_config).pack(side=tk.LEFT)
        row_idx += 1

        # ===== 产品信息区域 =====
        ttk.Separator(parent, orient=tk.HORIZONTAL).grid(
            row=row_idx, column=0, columnspan=2, sticky=tk.EW, pady=4)
        row_idx += 1

        ttk.Label(parent, text="产品信息（固定值，不包含SN和序列号）",
                  font=("Microsoft YaHei", 10, "bold")).grid(
            row=row_idx, column=0, columnspan=2, sticky=tk.W, pady=(4, 2))
        row_idx += 1

        # 定义所有固定字段（不包含物理SN和SN，它们属于逐条输入区）
        self.fixed_fields = {}
        field_defs = [
            # (字段名, 标签, 是否必填, 默认值)
            ("product_type", "产品类型 *", True, ""),
            ("product_name", "产品名称 *", True, ""),
            ("product_model", "产品型号 *", True, ""),
            ("sales_channel", "销售渠道", False, ""),
            ("manual_link", "说明书链接", False, ""),
            ("remarks", "备注", False, ""),
            ("image", "图片链接", False, ""),
        ]

        for field_name, label, required, default in field_defs:
            ttk.Label(parent, text=f"{label}:", width=12).grid(
                row=row_idx, column=0, sticky=tk.E, padx=(0, 4), pady=2)
            var = tk.StringVar(value=default)
            entry = ttk.Entry(parent, textvariable=var, width=52)
            entry.grid(row=row_idx, column=1, sticky=tk.EW, pady=2)
            self.fixed_fields[field_name] = {"var": var, "required": required, "label": label}
            row_idx += 1

        # ===== 批量导入区域 =====
        ttk.Separator(parent, orient=tk.HORIZONTAL).grid(
            row=row_idx, column=0, columnspan=2, sticky=tk.EW, pady=6)
        row_idx += 1

        ttk.Label(parent, text="批量导入（逐条输入）",
                  font=("Microsoft YaHei", 10, "bold")).grid(
            row=row_idx, column=0, columnspan=2, sticky=tk.W, pady=(4, 2))
        row_idx += 1

        # 序列号输入
        ttk.Label(parent, text="物理SN:", width=12).grid(row=row_idx, column=0, sticky=tk.E, padx=(0, 4), pady=2)
        self.sn_code_var = tk.StringVar()
        self.sn_code_entry = ttk.Entry(parent, textvariable=self.sn_code_var, width=52, font=("Microsoft YaHei", 10))
        self.sn_code_entry.grid(row=row_idx, column=1, sticky=tk.EW, pady=2)
        self.sn_code_entry.bind("<Return>", self.on_sn_code_enter)
        row_idx += 1

        # SN输入
        ttk.Label(parent, text="SN (序列号):", width=12).grid(row=row_idx, column=0, sticky=tk.E, padx=(0, 4), pady=2)
        self.sn_var = tk.StringVar()
        self.sn_entry = ttk.Entry(parent, textvariable=self.sn_var, width=52, font=("Microsoft YaHei", 10))
        self.sn_entry.grid(row=row_idx, column=1, sticky=tk.EW, pady=2)
        self.sn_entry.bind("<Return>", self.on_sn_enter)
        row_idx += 1

        # 提示信息
        ttk.Label(parent, text="物理SN输入完按回车 → 自动跳转到SN输入 → SN输入完按回车 → 自动提交",
                  font=("Microsoft YaHei", 8), foreground="gray").grid(
            row=row_idx, column=0, columnspan=2, sticky=tk.W, pady=(2, 4))
        row_idx += 1

        # 提交按钮
        ttk.Button(parent, text="提交（发送POST请求）", command=self.submit_current).grid(
            row=row_idx, column=0, columnspan=2, pady=(0, 6))
        row_idx += 1

        # 配置 grid 列权重
        parent.columnconfigure(1, weight=1)

    # ==================== 口令显示切换 ====================
    def toggle_token_visibility(self):
        if self.show_token_var.get():
            self.token_entry.configure(show="")
        else:
            self.token_entry.configure(show="*")

    # ==================== 日志输出 ====================
    def log(self, message, level="info"):
        """输出日志到左侧编辑框和文件"""
        now = datetime.now().strftime("%H:%M:%S")
        prefix_map = {
            "success": "[✓]",
            "error": "[✗]",
            "info": "[→]",
            "start": "[▸]",
        }
        prefix = prefix_map.get(level, "[→]")
        full_msg = f"{now} {prefix} {message}"

        self.log_text.configure(state=tk.NORMAL)
        self.log_text.insert(tk.END, full_msg + "\n")
        self.log_text.see(tk.END)
        self.log_text.configure(state=tk.DISABLED)

        # 同时写入 .log 文件
        if level == "error":
            logger.error(message)
        elif level == "success":
            logger.info(f"SUCCESS: {message}")
        else:
            logger.info(message)

    def clear_log(self):
        """清空日志显示"""
        self.log_text.configure(state=tk.NORMAL)
        self.log_text.delete("1.0", tk.END)
        self.log_text.configure(state=tk.DISABLED)
        self.total_count = 0
        self.success_count = 0
        self.fail_count = 0
        self.update_stats()

    def update_stats(self):
        """更新统计标签"""
        self.stats_label.configure(
            text=f"总计: {self.total_count}  |  成功: {self.success_count}  |  失败: {self.fail_count}"
        )

    # ==================== 物理SN回车 → 跳转SN ====================
    def on_sn_code_enter(self, event):
        """物理SN输入完按回车 → 自动跳转到SN输入框"""
        if self.sn_code_var.get().strip():
            self.sn_entry.focus_set()
            self.sn_entry.select_range(0, tk.END)
        return "break"

    # ==================== SN回车 → 直接提交 ====================
    def on_sn_enter(self, event):
        """SN输入完按回车 → 直接发送POST请求"""
        self.submit_current()
        return "break"

    # ==================== 校验输入 ====================
    def validate_inputs(self):
        """校验必填字段和URL"""
        errors = []

        # 校验调用地址
        api_url = self.api_url_var.get().strip()
        if not api_url or api_url == "https://":
            errors.append("请输入调用地址")

        # 校验必填的固定字段
        for field_name, field_info in self.fixed_fields.items():
            if field_info["required"]:
                val = field_info["var"].get().strip()
                if not val:
                    errors.append(f"请输入必填字段: {field_info['label']}")

        # 校验SN
        sn = self.sn_var.get().strip()
        if not sn:
            errors.append("请输入SN（序列号）")

        return errors

    # ==================== 构建请求数据 ====================
    def build_request_data(self):
        """构建POST请求的JSON数据"""
        sn = self.sn_var.get().strip()
        sn_code = self.sn_code_var.get().strip()

        data = {
            "sn": sn,
            "product_type": self.fixed_fields["product_type"]["var"].get().strip(),
            "product_name": self.fixed_fields["product_name"]["var"].get().strip(),
            "product_model": self.fixed_fields["product_model"]["var"].get().strip(),
        }

        # 可选字段：仅非空时发送
        if sn_code:
            data["sn_code"] = sn_code
        for opt_field in ["sales_channel", "manual_link", "remarks", "image", "sn_code"]:
            if opt_field == "sn_code":
                continue  # 已处理
            val = self.fixed_fields[opt_field]["var"].get().strip()
            if val:
                data[opt_field] = val

        return data

    # ==================== 提交请求 ====================
    def submit_current(self):
        """提交当前SN"""
        # 校验
        errors = self.validate_inputs()
        if errors:
            messagebox.showwarning("输入不完整", "\n".join(errors))
            return

        # 构建数据
        data = self.build_request_data()
        api_url = self.api_url_var.get().strip()
        token = self.token_var.get().strip()

        # 构建完整URL（带token参数）
        url = api_url
        if token:
            separator = "&" if "?" in url else "?"
            url = f"{url}{separator}token={token}"

        # 日志记录请求信息
        sn = data["sn"]
        sn_code = data.get("sn_code", "")
        self.log(f"发送: SN={sn} | sn_code={sn_code} | type={data['product_type']} | name={data['product_name']}",
                 "start")
        self.log(f"请求JSON: {json.dumps(data, ensure_ascii=False)}", "info")

        # 后台线程发送请求，避免阻塞UI
        Thread(target=self.do_request, args=(url, data, sn), daemon=True).start()

    def do_request(self, url, data, sn):
        """在后台线程执行HTTP请求"""
        try:
            resp = self.session.post(url, json=data, timeout=15)
            resp_data = resp.json() if resp.text else {}

            # 回到主线程更新UI
            self.root.after(0, self.handle_response, resp, resp_data, data, sn, None)

        except requests.exceptions.ConnectionError as e:
            self.root.after(0, self.handle_response, None, None, data, sn, f"连接失败: {str(e)}")
        except requests.exceptions.Timeout:
            self.root.after(0, self.handle_response, None, None, data, sn, "请求超时（15秒）")
        except json.JSONDecodeError:
            self.root.after(0, self.handle_response, None, None, data, sn, "服务器返回非JSON格式数据")
        except Exception as e:
            self.root.after(0, self.handle_response, None, None, data, sn, f"请求异常: {str(e)}")

    def handle_response(self, resp, resp_data, request_data, sn, error_msg):
        """处理服务器响应"""
        self.total_count += 1

        if error_msg:
            self.log(f"失败: SN={sn} → {error_msg}", "error")
            self.fail_count += 1
            self.update_stats()
            return

        http_code = resp.status_code
        success = resp_data.get("success", False)

        if http_code == 200 and success:
            server_msg = resp_data.get("message", "保存成功")
            new_id = resp_data.get("id", "?")
            self.log(f"成功: SN={sn} → ID={new_id}, {server_msg}", "success")
            self.success_count += 1

            # 成功后清空SN和物理SN输入框，聚焦到物理SN
            self.sn_var.set("")
            self.sn_code_var.set("")
            self.sn_code_entry.focus_set()

        else:
            err_msg = resp_data.get("error", f"HTTP {http_code}")
            self.log(f"失败: SN={sn} → {err_msg}", "error")
            self.fail_count += 1

            # 失败时选中SN内容方便重新输入
            self.sn_entry.focus_set()
            self.sn_entry.select_range(0, tk.END)

        self.update_stats()

    # ==================== 模板保存 ====================
    def get_all_field_values(self):
        """获取所有可保存的字段值（不包含SN）"""
        return {
            "api_url": self.api_url_var.get().strip(),
            "token": self.token_var.get().strip(),
            "product_type": self.fixed_fields["product_type"]["var"].get().strip(),
            "product_name": self.fixed_fields["product_name"]["var"].get().strip(),
            "product_model": self.fixed_fields["product_model"]["var"].get().strip(),
            "sales_channel": self.fixed_fields["sales_channel"]["var"].get().strip(),
            "manual_link": self.fixed_fields["manual_link"]["var"].get().strip(),
            "remarks": self.fixed_fields["remarks"]["var"].get().strip(),
            "image": self.fixed_fields["image"]["var"].get().strip(),
        }

    def save_template(self):
        """保存当前配置为INI模板"""
        values = self.get_all_field_values()
        config = configparser.ConfigParser()
        config["Settings"] = values

        try:
            with open(CONFIG_FILE, "w", encoding="utf-8") as f:
                config.write(f)
            # 同时保存为"上次配置"
            with open(LAST_CONFIG_FILE, "w", encoding="utf-8") as f:
                config.write(f)
            self.log(f"模板已保存到: {CONFIG_FILE}", "success")
            messagebox.showinfo("保存成功", f"模板已保存到:\n{CONFIG_FILE}")
        except Exception as e:
            messagebox.showerror("保存失败", f"无法保存模板:\n{str(e)}")

    def load_template(self):
        """从INI模板加载配置"""
        if not os.path.exists(CONFIG_FILE):
            messagebox.showwarning("模板不存在", f"未找到模板文件:\n{CONFIG_FILE}\n\n请先保存模板。")
            return

        config = configparser.ConfigParser()
        try:
            config.read(CONFIG_FILE, encoding="utf-8")
            if "Settings" not in config:
                raise ValueError("模板文件格式不正确")
            self.apply_config(config["Settings"])
            self.log(f"已从模板加载配置: {CONFIG_FILE}", "success")
            messagebox.showinfo("导入成功", f"已从模板加载配置。")
        except Exception as e:
            messagebox.showerror("导入失败", f"无法读取模板:\n{str(e)}")

    def auto_load_last_config(self):
        """自动加载上次配置（启动时和点击恢复时调用）"""
        if not os.path.exists(LAST_CONFIG_FILE):
            return

        config = configparser.ConfigParser()
        try:
            config.read(LAST_CONFIG_FILE, encoding="utf-8")
            if "Settings" in config:
                self.apply_config(config["Settings"])
                self.log("已自动恢复上次配置", "info")
        except Exception:
            pass  # 静默失败

    def apply_config(self, settings):
        """应用配置到界面"""
        if settings.get("api_url"):
            self.api_url_var.set(settings["api_url"])
        if settings.get("token"):
            self.token_var.set(settings["token"])
        for field_name in self.fixed_fields:
            if field_name in settings and settings[field_name]:
                self.fixed_fields[field_name]["var"].set(settings[field_name])

    # ==================== 关闭事件 ====================
    def on_close(self):
        """窗口关闭时自动保存配置"""
        try:
            values = self.get_all_field_values()
            config = configparser.ConfigParser()
            config["Settings"] = values
            with open(LAST_CONFIG_FILE, "w", encoding="utf-8") as f:
                config.write(f)
        except Exception:
            pass
        self.root.destroy()


# ==================== 入口 ====================
def main():
    root = tk.Tk()

    # 设置样式
    style = ttk.Style()
    try:
        # 尝试使用系统主题
        available_themes = style.theme_names()
        if "vista" in available_themes:
            style.theme_use("vista")
        elif "clam" in available_themes:
            style.theme_use("clam")
    except Exception:
        pass

    app = FactoryImportApp(root)
    root.mainloop()


if __name__ == "__main__":
    # 如果缺少 requests 库，提示安装
    try:
        import requests  # noqa: F811
    except ImportError:
        print("=" * 60)
        print("缺少 requests 库！")
        print("请运行: pip install requests")
        print("=" * 60)
        if len(sys.argv) > 1 and sys.argv[1] == "--install":
            os.system(f"{sys.executable} -m pip install requests")
            print("安装完成，请重新运行程序。")
        sys.exit(1)

    main()