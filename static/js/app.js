
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
                            <button onclick="render_login()">تلاش مجدد</button>
                        `;
                    }

                } catch (error) {
                    console.error(error);

                    auth_box.innerHTML = `
                        <h2>خطا در بررسی کد!</h2>
                        <button onclick="render_login()">تلاش مجدد</button>
                    `;
                }
            });

        } catch (error) {
            console.error(error);

            auth_box.innerHTML = `
                <h2>خطا در ارسال درخواست!</h2>
                <button onclick="render_login()">تلاش مجدد</button>
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

const update = (new_state) => {
    state = new_state;

    if (state === 0) {
        document.title = 'Li-Deploy';
        render_main();
    } else if (state === 1) {
        document.title = 'Li-Deploy | ورود';
        render_login();
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
    //
}

update(0);
