import ClipboardJS from 'clipboard';
import '@master/css';
import './admin.scss';

document.querySelectorAll('.btn-copy').forEach(btn => {
    btn.addEventListener('click', e => {
        e.preventDefault()
        e.target.querySelector('span').style.opacity = 1;
        setTimeout(() => {
            e.target.querySelector('span').style.opacity = 0;
        }, 1500);
    })
})

let btnCopy = new ClipboardJS('.btn-copy');

document.querySelectorAll('.form-notify-params-toggle').forEach(e => {
    e.classList.add('active')
})
document.querySelectorAll('.form-notify-params-toggle h3').forEach(btn => {
    btn.addEventListener('click', e => {
        e.preventDefault()
        e.target.parentElement.classList.toggle('active')
    })
})




