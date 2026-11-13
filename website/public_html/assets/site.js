(() => {
  const header=document.getElementById('site-header');
  const menuButton=document.getElementById('menu-toggle');
  const nav=document.getElementById('primary-nav');
  const dropdownButtons=[...document.querySelectorAll('.dropdown-toggle')];
  const closeDropdowns=(except=null)=>dropdownButtons.forEach(button=>{
    if(button!==except){button.setAttribute('aria-expanded','false');document.getElementById(button.getAttribute('aria-controls')).hidden=true;}
  });
  const closeMenu=()=>{menuButton.setAttribute('aria-expanded','false');menuButton.setAttribute('aria-label','Open menu');nav.classList.remove('open');closeDropdowns();};
  const syncHeader=()=>header.classList.toggle('scrolled',window.scrollY>18);
  syncHeader();window.addEventListener('scroll',syncHeader,{passive:true});
  menuButton.addEventListener('click',()=>{
    const open=menuButton.getAttribute('aria-expanded')!=='true';
    menuButton.setAttribute('aria-expanded',String(open));menuButton.setAttribute('aria-label',open?'Close menu':'Open menu');nav.classList.toggle('open',open);
    if(!open)closeDropdowns();
  });
  dropdownButtons.forEach(button=>button.addEventListener('click',()=>{
    const open=button.getAttribute('aria-expanded')!=='true';closeDropdowns(button);
    button.setAttribute('aria-expanded',String(open));document.getElementById(button.getAttribute('aria-controls')).hidden=!open;
  }));
  document.addEventListener('click',event=>{if(!header.contains(event.target))closeMenu();});
  document.addEventListener('keydown',event=>{
    if(event.key==='Escape'){
      const active=dropdownButtons.find(button=>button.getAttribute('aria-expanded')==='true');
      if(active){closeDropdowns();active.focus();}else if(nav.classList.contains('open')){closeMenu();menuButton.focus();}
    }
  });
  nav.querySelectorAll('a').forEach(a=>a.addEventListener('click',closeMenu));
  window.matchMedia('(min-width:1181px)').addEventListener('change',event=>{if(event.matches)closeMenu();});

  const tabs=[...document.querySelectorAll('[role="tab"]')];
  function activate(tab,focus=false){
    tabs.forEach(item=>{
      const selected=item===tab;item.setAttribute('aria-selected',String(selected));item.tabIndex=selected?0:-1;
      document.getElementById(item.getAttribute('aria-controls')).hidden=!selected;
    });
    if(focus)tab.focus();
  }
  tabs.forEach((tab,index)=>{
    tab.addEventListener('click',()=>activate(tab));
    tab.addEventListener('keydown',event=>{
      let next=null;
      if(event.key==='ArrowRight')next=(index+1)%tabs.length;
      if(event.key==='ArrowLeft')next=(index-1+tabs.length)%tabs.length;
      if(event.key==='Home')next=0;if(event.key==='End')next=tabs.length-1;
      if(next!==null){event.preventDefault();activate(tabs[next],true);}
    });
  });
})();
