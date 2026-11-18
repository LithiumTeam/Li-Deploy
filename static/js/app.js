
var app = document.getElementById('app');
var state = 0;

const render_login = () => {
    app.innerHTML = `
        <div id="auth-box">
            <h1>ورود به حساب</h1>
            <input placeholder="ایمیل (پست الکترونیکی)" name="email" type="email" required>
            <br><br>
            <button type="submit" id="continue-login">ادامه</button>
        </div>
    `;

    let auth_box = document.getElementById('auth-box');

    const send_email = async (email) => {
        let response = await fetch('/api/login/index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                to: email
            })
        });

        let result = await response.json();

        if (!response.ok) {
            throw new Error(result.error || 'Request failed');
        }

        return result;
    };

    const check_code = async (code) => {
        let response = await fetch('/api/verify-login/index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                code: code
            })
        });

        let result = await response.json();

        if (!response.ok) {
            throw new Error(result.error || 'Request failed');
        }

        return result;
    };

    document.getElementById('continue-login').addEventListener('click', async () => {
        let email_input = document.getElementsByName('email')[0];
        let email = email_input.value.trim();

        if (!email) {
            alert('لطفاً ایمیل خود را وارد کنید.');
            return;
        }

        if (!email_input.checkValidity()) {
            alert('ایمیل وارد شده معتبر نیست.');
            return;
        }

        auth_box.innerHTML = `
            <div class="loader"></div>
        `;

        try {
            let result = await send_email(email);

            if (!result.success) {
                throw new Error(result.error || 'Email sending failed');
            }

            auth_box.innerHTML = `
                <h1>کد ورود</h1>
                <input placeholder="کد ورود" name="code" type="text" inputmode="numeric" maxlength="4" required>
                <br><br>
                <button type="submit" id="submit-login">ادامه</button>
                <pre style="color: gray; font-size: 13px;">یک کد به حساب ایمیل شما ارسال شده.
<a href="https://gmail.com/" target="_blank">لینک به ایمیل باکس شما</a></pre>
            `;

            document.getElementById('submit-login').addEventListener('click', async () => {
                let code_input = document.getElementsByName('code')[0];
                let code = code_input.value.trim();

                if (!code) {
                    alert('لطفاً کد خود را وارد کنید.');
                    return;
                }

                if (!/^\d{4}$/.test(code)) {
                    alert('کد ورود باید ۴ رقم باشد.');
                    return;
                }

                auth_box.innerHTML = `
                    <div class="loader"></div>
                `;

                try {
                    let result = await check_code(code);

                    if (result.success) {
                        alert('با موفقیت وارد حساب کاربری شدید!');
                        window.location.href = '/home';
                    } else {
                        auth_box.innerHTML = `
                            <h2>کد وارد شده معتبر نیست!</h2>
                            <button type="submit" onclick="render_login()">تلاش مجدد</button>
                        `;
                    }

                } catch (error) {
                    console.error(error);

                    auth_box.innerHTML = `
                        <h2>خطا در بررسی کد!</h2>
                        <button type="submit" onclick="render_login()">تلاش مجدد</button>
                    `;
                }
            });

        } catch (error) {
            console.error(error);

            auth_box.innerHTML = `
                <h2>خطا در ارسال درخواست!</h2>
                <button type="submit" onclick="render_login()">تلاش مجدد</button>
            `;
        }
    });
};

const render_main = () => {
    app.innerHTML = `
        <div>
            <h1>Li-Deploy</h1>
            <pre>سرویس آپلود فایل های PHP شما برای تست در دپلویمنت.
با قابلیت های متنوع.</pre>
        </div>
    `;
};

const render_dashboard = () => {
    app.innerHTML = `
        <div>
            <h1>داشبورد</h1>
            <div>
                <hr>
                <div class="flex">
                    <div>
                        <span>سلام <span style="color: var(--accent);">macsepehri</span>!</span><br>
                        <span>تعداد پروژه ها : <span style="color: var(--accent);">${total_projects}</span></span><br><br>
                        <button id="logout-button">خروج از حساب</button>
                    </div>
                    <div>
                        <button id="create-project" type="submit">ساخت پروژه</button><br><br>
                        <button type="submit">حذف پروژه</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('logout-button').addEventListener('click',(ev)=>{
        window.location.href = '/api/logout/index.php';
    });

    document.getElementById('create-project').addEventListener('click', () => {
        document.title = 'Li-Deploy | ساخت پروژه';

        app.innerHTML = `
            <div>
                <h1>ساخت پروژه جدید</h1>
                <hr>

                <form id="project-form">
                    <div>
                        <input type="text" id="project-name" placeholder="نام پروژه" required>
                    </div>
                    <br>

                    <div>
                        <input type="file" id="php-source" accept=".php" hidden required>

                        <label for="php-source" class="btn">
                            آپلود سورس PHP
                        </label>

                        <p style="color: gray; font-size: 13px;">
                            این سورس در صفحه ایندکس (" / ") قرار می‌گیرد.
                        </p>
                    </div>

                    <div id="upload-result"></div>
                </form>
            </div>
        `;

        const fileInput = document.getElementById('php-source');

        fileInput.addEventListener('change', async () => {
            const file = fileInput.files[0];

            if (!file) return;

            const projectName = document.getElementById('project-name').value.trim();

            if (!projectName) {
                alert('لطفاً نام پروژه را وارد کنید.');
                fileInput.value = '';
                return;
            }

            if (!/^[a-zA-Z0-9_-]+$/.test(projectName)) {
                alert('نام پروژه فقط می‌تواند شامل حروف انگلیسی، اعداد، - و _ باشد.');
                fileInput.value = '';
                return;
            }

            if (!file.name.toLowerCase().endsWith('.php')) {
                alert('فقط فایل PHP مجاز است.');
                fileInput.value = '';
                return;
            }

            app.innerHTML = `
                <div class="loader"></div>
            `;

            try {
                const source = await file.text();

                const response = await fetch('/api/project/valid/index.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        project_name: projectName,
                        source: source
                    })
                });

                const data = await response.json();
                let msg;
                let desc;
                let button_info;
                if (data.message.trim()==='in-valid') {
                    msg = 'خطا در ساخت پروژه';
                    desc = 'در سورس کد، کد هایی مربوط به فایل ها، دیتابیس هست که میتونه باعث مشکلات برای وبسایت بشه نظیر لو رفتن داده و غیره.';
                    button_info = ['back-dashboard','بازگشت به داشبورد'];
                } else {
                    msg = 'پروژه ساخته شد';
                    desc = 'پروژه با موفقیت ساخته شد.';
                    button_info = ['open-project','ادامه'];
                }
                app.innerHTML = `
                    <div class="upload-status">
                        <h3>${msg}</h3>
                        <p style="width:220px;">${desc}</p>
                        <button class="btn" id="${button_info[0]}">${button_info[1]}</button>
                    </div>
                `;
                
                console.log(data.message);

                try {
                    document.getElementById('back-dashboard').addEventListener('click', () => {
                        update(2);
                    });
                } catch {
                    document.getElementById('open-project').addEventListener('click', () => {
                        app.innerHTML = ``;
                    });
                };

            } catch (error) {
                console.error(error);

                app.innerHTML = `
                    <div>
                        <h2>خطا در ارسال درخواست!</h2>
                        <p>ارتباط با سرور برقرار نشد.</p>
                        <button class="btn" id="back-dashboard">بازگشت به داشبورد</button>
                    </div>
                `;

                document.getElementById('back-dashboard').addEventListener('click', () => {
                    update(2);
                });
            }
        });
    });
};

const update = (new_state) => {
    state = new_state;

    if (state === 0) {
        document.title = 'Li-Deploy';
        render_main();
    } else if (state === 1) {
        document.title = 'Li-Deploy | ورود';
        render_login();
    } else if (state === 2) {
        if (!logged_in) {
            update(1);
        } else {
            document.title = 'Li-Deploy | داشبورد';
            render_dashboard();
        }
    }
};

document.getElementById('main-page').addEventListener('click', () => {
    update(0);
});

try {
    document.getElementById('login-page').addEventListener('click', () => {
        update(1);
    });
} catch {
    document.getElementById('dashboard-page').addEventListener('click', () => {
        update(2);
    });
}

update(0);
