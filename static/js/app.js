var app = document.getElementById('app');
var state = 0;

const render_login = () => {
    app.innerHTML = `

<div id="auth-box">
    <h1>ورود به حساب</h1>
    <input placeholder="ایمیل (پست الکترونیکی)" name="email" type="email"><br><br>
    <button type="submit" id="continue-login">ادامه</button>
</div>

`;

    let email = document.getElementsByName('email')[0];
    let auth_box = document.getElementById('auth-box');

    const send_email = async () => {
        try {
            let first = await fetch('https://github.com/');

            if (!first.ok) {
                throw new Error('Request failed');
            }

            let res = await first.text();
            return res;

        } catch (error) {
            throw error;
        }
    };

    document.getElementById('continue-login').addEventListener('click', async (ev) => {
        auth_box.innerHTML = `
<div class="loader"></div>
`;

        try {
            let result = await send_email();

            console.log(result);
            auth_box.innerHTML = 'درخواست با موفقیت ارسال شد!';

        } catch (error) {
            console.error(error);
            auth_box.innerHTML = 'خطا در ارسال درخواست!';
        }
    });

}
const render_main = () => {
    app.innerHTML = `
<div>
    <h1>Li-Deploy</h1>
    <pre>سرویس آپلود فایل های PHP شما برای تست در دپلویمنت.
با قابلیت های متنوع.</pre>
</div>
`;
}

const update = (new_state) => {
    console.log(new_state)
    state = new_state;
    if (state===0) {
        document.title = 'Li-Deploy';
        render_main();
    } else if (state===1) {
        document.title = 'Li-Deploy | ورود';
        render_login();
    }
}

document.getElementById('main-page').addEventListener('click',(ev)=>{ update(0); });
document.getElementById('login-page').addEventListener('click',(ev)=>{ update(1); });

update(0);