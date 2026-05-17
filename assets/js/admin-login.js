const signUpButton = document.getElementById('signUp');
const signInButton = document.getElementById('signIn');
const container = document.getElementById('container');
const lemaFooter = document.querySelector('.lema');

if (signUpButton && container && lemaFooter) {
    signUpButton.addEventListener('click', () => {
        container.classList.add('right-panel-active');
        lemaFooter.classList.add('naranja');
    });
}

if (signInButton && container && lemaFooter) {
    signInButton.addEventListener('click', () => {
        container.classList.remove('right-panel-active');
        lemaFooter.classList.remove('naranja');
    });
}

const currentYear = document.getElementById('currentYear');
if (currentYear) {
    currentYear.textContent = new Date().getFullYear();
}

const params = new URLSearchParams(window.location.search);
if (params.get('error') === 'datos') {
    alert('Datos incorrectos');
    window.history.replaceState({}, document.title, window.location.pathname);
}
