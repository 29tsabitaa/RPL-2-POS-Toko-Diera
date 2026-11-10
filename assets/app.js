const modal=document.getElementById('logoutModal');
document.querySelectorAll('[data-logout]').forEach(a=>a.addEventListener('click',e=>{e.preventDefault();modal.classList.add('show')}));
document.getElementById('lgBatal')?.addEventListener('click',()=>modal.classList.remove('show'));
modal?.addEventListener('click',e=>{if(e.target===modal)modal.classList.remove('show')});
document.querySelectorAll('[data-confirm]').forEach(f=>f.addEventListener('submit',e=>{if(!confirm(f.dataset.confirm))e.preventDefault()}));
