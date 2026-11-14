var app = document.getElementById('app');
var state = 0;

const render_login = () => {
    app.innerHTML = `

<div>
    <form action="/api/login" method="post">
        <h1>ورود به حساب</h1>
        <input placeholder="ایمیل (پست الکترونیکی)" name="email" type="email"><br><br>
        <button type="submit">ادامه</button>
    </form>
</div>

`;
}
const render_main = () => {
    app.innerHTML = `
    hello2`;
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