
var app = document.getElementById('app');
var state = 0;
var dashboardData = null;

const escapeHTML = (value) => {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[char]);
};

const request = async (url, options = {}) => {
    const response = await fetch(url, {
        credentials: 'same-origin',
        ...options
    });

    const data = await response.json();

    if (!response.ok || data.success === false) {
        throw new Error(data.message || data.error || 'Request failed');
    }

    return data;
};

const render_login = () => {
    app.innerHTML = `
        <div id="auth-box">
            <h1>ورود به حساب</h1>
            <input placeholder="ایمیل (پست الکترونیکی)" name="email" type="email" required>
            <br><br>
            <button type="button" id="continue-login">ادامه</button>
        </div>
    `;

    const authBox = document.getElementById('auth-box');

    const send_email = async (email) => {
        return await request('/api/login/index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                to: email
            })
        });
    };

    const check_code = async (code) => {
        return await request('/api/verify-login/index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                code: code
            })
        });
    };

    document.getElementById('continue-login').addEventListener('click', async () => {
        const emailInput = document.getElementsByName('email')[0];
        const email = emailInput.value.trim();

        if (!email || !emailInput.checkValidity()) {
            alert('لطفاً ایمیل معتبر وارد کنید.');
            return;
        }

        authBox.innerHTML = '<div class="loader"></div>';

        try {
            const result = await send_email(email);

            if (!result.success) {
                throw new Error(result.error || 'Email sending failed');
            }

            authBox.innerHTML = `
                <h1>کد ورود</h1>
                <input placeholder="کد ورود" name="code" type="text" inputmode="numeric" maxlength="4" required>
                <br><br>
                <button type="button" id="submit-login">ادامه</button>
                <pre style="color: gray; font-size: 13px;">یک کد به حساب ایمیل شما ارسال شده.
<a href="https://gmail.com/" target="_blank" rel="noopener noreferrer">لینک به ایمیل باکس شما</a></pre>
            `;

            document.getElementById('submit-login').addEventListener('click', async () => {
                const codeInput = document.getElementsByName('code')[0];
                const code = codeInput.value.trim();

                if (!/^\d{4}$/.test(code)) {
                    alert('کد ورود باید ۴ رقم باشد.');
                    return;
                }

                authBox.innerHTML = '<div class="loader"></div>';

                try {
                    const result = await check_code(code);

                    if (result.success) {
                        alert('با موفقیت وارد حساب کاربری شدید!');
                        window.location.href = '/home';
                    } else {
                        throw new Error(result.error || 'Incorrect code');
                    }

                } catch (error) {
                    console.error(error);

                    authBox.innerHTML = `
                        <h2>کد وارد شده معتبر نیست!</h2>
                        <button type="button" id="retry-login">تلاش مجدد</button>
                    `;

                    document.getElementById('retry-login').addEventListener('click', render_login);
                }
            });

        } catch (error) {
            console.error(error);

            authBox.innerHTML = `
                <h2>خطا در ارسال درخواست!</h2>
                <p>${escapeHTML(error.message)}</p>
                <button type="button" id="retry-login">تلاش مجدد</button>
            `;

            document.getElementById('retry-login').addEventListener('click', render_login);
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

const render_project_management = async (projectCode) => {
    document.title = 'Li-Deploy | مدیریت پروژه';

    app.innerHTML = `
        <div>
            <h1>مدیریت پروژه</h1>
            <hr>
            <div class="loader"></div>
            <button class="btn" id="back-dashboard">بازگشت به داشبورد</button>
        </div>
    `;

    document.getElementById('back-dashboard').addEventListener('click', () => {
        update(2);
    });

    try {
        if (!projectCode) {
            throw new Error('کد پروژه مشخص نشده است.');
        }

        const data = await request('/api/project/get', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                code: projectCode
            })
        });

        const project = data.project;

        if (!project) {
            throw new Error('اطلاعات پروژه دریافت نشد.');
        }

        app.innerHTML = `
            <div>
                <h1>مدیریت پروژه</h1>
                <hr>

                <div>
                    <h2>${escapeHTML(project.name)}</h2>
                    <p>کد پروژه: ${escapeHTML(project.code)}</p>
                    <p>مدیر: ${escapeHTML(project.manager)}</p>
                    <p>
                        لینک پروژه:
                        <a href="${escapeHTML(project.url)}" target="_blank" rel="noopener noreferrer">
                            ${escapeHTML(project.url)}
                        </a>
                    </p>
                    <p>تاریخ ساخت: ${escapeHTML(project.created_at)}</p>
                </div>

                <br>
                <button class="btn" id="back-dashboard">
                    بازگشت به داشبورد
                </button>
            </div>
        `;

        document.getElementById('back-dashboard').addEventListener('click', () => {
            update(2);
        });

    } catch (error) {
        console.error(error);

        app.innerHTML = `
            <div>
                <h2>خطا در دریافت پروژه</h2>
                <p>${escapeHTML(error.message)}</p>
                <button class="btn" id="back-dashboard">
                    بازگشت به داشبورد
                </button>
            </div>
        `;

        document.getElementById('back-dashboard').addEventListener('click', () => {
            update(2);
        });
    }
};

const render_dashboard = async () => {
    document.title = 'Li-Deploy | داشبورد';

    app.innerHTML = '<div class="loader"></div>';

    try {
        const data = await request('/api/project/check');

        dashboardData = data;

        const totalProjects = Number(data.total_projects || 0);
        const hasProjects = data.has_projects === true;

        let content = '';

        if (hasProjects && data.project_code) {
            content = '<button id="open-project" class="btn">مشاهده پروژه</button>';
        } else {
            content = '<button id="create-project" class="btn" type="button">ساخت پروژه</button>';
        }

        const deleteButton = hasProjects
            ? '<br><br><button id="delete-project" class="btn">حذف پروژه</button>'
            : '';

        app.innerHTML = `
            <div>
                <h1>داشبورد</h1>
                <div>
                    <hr>
                    <div class="flex">
                        <div>
                            <span>سلام <span style="color: var(--accent);">${escapeHTML(data.user_email || '')}</span>!</span>
                            <br>
                            <span>تعداد پروژه ها : <span style="color: var(--accent);">${totalProjects}</span></span>
                            <br><br>
                            <button id="logout-button">خروج از حساب</button>
                        </div>

                        <div>
                            ${content}
                            ${deleteButton}
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('logout-button').addEventListener('click', () => {
            window.location.href = '/api/logout/index.php';
        });

        const createButton = document.getElementById('create-project');

        if (createButton) {
            createButton.addEventListener('click', render_create_project);
        }

        const openButton = document.getElementById('open-project');

        if (openButton) {
            openButton.addEventListener('click', () => {
                render_project_management(data.project_code);
            });
        }

        const deleteProjectBtn = document.getElementById('delete-project');

        if (deleteProjectBtn) {
            deleteProjectBtn.addEventListener('click', async () => {
                if (!confirm('آیا از حذف پروژه اطمینان دارید؟')) {
                    return;
                }

                deleteProjectBtn.disabled = true;

                try {
                    await request('/api/project/delete/index.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            code: data.project_code
                        })
                    });

                    alert('پروژه با موفقیت حذف شد.');
                    update(2);
                } catch (error) {
                    console.error(error);
                    alert('حذف پروژه انجام نشد: ' + error.message);
                    deleteProjectBtn.disabled = false;
                }
            });
        }

    } catch (error) {
        console.error(error);

        app.innerHTML = `
            <div>
                <h2>خطا در دریافت اطلاعات داشبورد</h2>
                <p>${escapeHTML(error.message)}</p>
                <button class="btn" id="retry-dashboard">تلاش مجدد</button>
                <button class="btn" id="back-login">ورود به حساب</button>
            </div>
        `;

        document.getElementById('retry-dashboard').addEventListener('click', render_dashboard);

        document.getElementById('back-login').addEventListener('click', () => {
            update(1);
        });
    }
};

const render_create_project = () => {
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
                    <br><br>
                    <button class="btn" id="back-dashboard">
                        بازگشت به داشبورد
                    </button>

                    <p style="color: gray; font-size: 13px;">
                        این سورس در صفحه ایندکس ("/") قرار می‌گیرد.
                    </p>
                </div>

                <div id="upload-result"></div>
            </form>
        </div>
    `;

    document.getElementById('back-dashboard').addEventListener('click', () => {
        update(2);
    });

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

        app.innerHTML = '<div class="loader"></div>';

        try {
            const source = await file.text();

            const data = await request('/api/project/valid/index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    project_name: projectName,
                    source: source
                })
            });

            if (
                data.validation === 'in-valid' ||
                data.message === 'in-valid'
            ) {
                throw new Error('سورس پروژه معتبر نیست.');
            }

            if (!data.project || !data.project.code) {
                throw new Error('پروژه ساخته شد اما کد پروژه از سرور دریافت نشد.');
            }

            app.innerHTML = `
                <div class="upload-status">
                    <h3>پروژه ساخته شد</h3>
                    <p style="width:220px;">پروژه با موفقیت ساخته شد.</p>
                    <p>کد پروژه: ${escapeHTML(data.project.code)}</p>
                    <button class="btn" id="open-project">ادامه</button>
                </div>
            `;

            document.getElementById('open-project').addEventListener('click', () => {
                render_project_management(data.project.code);
            });

        } catch (error) {
            console.error(error);

            app.innerHTML = `
                <div class="upload-status">
                    <h3>خطا در ساخت پروژه</h3>
                    <p>${escapeHTML(error.message)}</p>
                    <button class="btn" id="back-dashboard">بازگشت به داشبورد</button>
                </div>
            `;

            document.getElementById('back-dashboard').addEventListener('click', () => {
                update(2);
            });
        }
    });
};

const update = (newState) => {
    state = newState;

    if (state === 0) {
        document.title = 'Li-Deploy';
        render_main();
    } else if (state === 1) {
        document.title = 'Li-Deploy | ورود';
        render_login();
    } else if (state === 2) {
        render_dashboard();
    }
};

const mainPage = document.getElementById('main-page');
const loginPage = document.getElementById('login-page');
const dashboardPage = document.getElementById('dashboard-page');

if (mainPage) {
    mainPage.addEventListener('click', () => update(0));
}

if (loginPage) {
    loginPage.addEventListener('click', () => update(1));
}

if (dashboardPage) {
    dashboardPage.addEventListener('click', () => update(2));
}

update(0);
