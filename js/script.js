const open_sidebar = document.getElementById('open_sidebar');
const icon_sidebar = document.getElementById('icon_sidebar');
const menu_sidebar = document.getElementById('menu_sidebar');
const images_menu = document.querySelectorAll('menu_sidebar img');
let account = [];
let user_name = document.getElementById('name');
let user_surname = document.getElementById('surname');
let password = document.getElementById('password');
let phone = document.getElementById('phone');
let email = document.getElementById('email');


open_sidebar.addEventListener('click', sidebar);
open_sidebar.addEventListener('mouseleave', () => {
    images_menu.style = 'position:relative';
    icon_sidebar.style = 'display: flex';   
    menu_sidebar.style = 'display:none';
    open_sidebar.className = 'sidebar_out';
});

function sidebar() {
    images_menu.style = 'position:relative';
    icon_sidebar.style = 'display: none';   
    menu_sidebar.style = 'display:flex';
    open_sidebar.className = 'sidebar_active';
}

function register(account) {
    user_name = name.value;
    user_surname = surname.value;
    password = password.value;
    email = email.value;
    phone = phone.value; 
}