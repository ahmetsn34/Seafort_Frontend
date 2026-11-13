(() => {
  'use strict';
  const carousel=document.getElementById('hero-carousel');
  if(!carousel)return;
  const slides=[...carousel.querySelectorAll('[data-slide]')];
  const controls=carousel.querySelector('.carousel-controls');
  if(slides.length<2||!controls)return;
  const dots=[...controls.querySelectorAll('[data-go-to]')];
  const playback=controls.querySelector('[data-playback]');
  const playbackLabel=playback.querySelector('[data-playback-label]');
  const counter=controls.querySelector('[data-current]');
  const announcement=carousel.querySelector('[data-slide-announcement]');
  const reducedMotion=window.matchMedia('(prefers-reduced-motion: reduce)');
  let index=0;
  let timer=null;
  let userPaused=reducedMotion.matches;
  let hovering=false;
  let focusInside=false;

  function clearTimer(){if(timer!==null){window.clearTimeout(timer);timer=null;}}
  function updatePlayback(){
    playback.dataset.paused=String(userPaused);
    playback.setAttribute('aria-label',userPaused?'Play slideshow':'Pause slideshow');
    playbackLabel.textContent=userPaused?'Play':'Pause';
  }
  function schedule(){
    clearTimer();
    if(!userPaused&&!hovering&&!focusInside&&!document.hidden)timer=window.setTimeout(()=>show(index+1,false),4500);
  }
  function show(next,manual){
    index=(next+slides.length)%slides.length;
    slides.forEach((slide,i)=>{
      const active=i===index;
      slide.hidden=!active;
      slide.inert=!active;
      if(active)dots[i].setAttribute('aria-current','true');else dots[i].removeAttribute('aria-current');
    });
    counter.textContent=String(index+1).padStart(2,'0');
    if(manual){
      userPaused=true;
      announcement.textContent='Slide '+(index+1)+' of '+slides.length+'. '+slides[index].querySelector('.carousel-title').textContent;
      updatePlayback();
    }
    schedule();
  }

  controls.hidden=false;
  controls.querySelector('[data-previous]').addEventListener('click',()=>show(index-1,true));
  controls.querySelector('[data-next]').addEventListener('click',()=>show(index+1,true));
  dots.forEach(dot=>dot.addEventListener('click',()=>show(Number(dot.dataset.goTo),true)));
  playback.addEventListener('click',()=>{userPaused=!userPaused;updatePlayback();schedule();});
  carousel.addEventListener('keydown',event=>{
    if(event.target!==carousel&&!controls.contains(event.target))return;
    let next=null;
    if(event.key==='ArrowRight')next=index+1;
    if(event.key==='ArrowLeft')next=index-1;
    if(event.key==='Home')next=0;
    if(event.key==='End')next=slides.length-1;
    if(next!==null){event.preventDefault();show(next,true);}
  });
  carousel.addEventListener('mouseenter',()=>{hovering=true;clearTimer();});
  carousel.addEventListener('mouseleave',()=>{hovering=false;schedule();});
  carousel.addEventListener('focusin',()=>{focusInside=true;clearTimer();});
  carousel.addEventListener('focusout',event=>{focusInside=carousel.contains(event.relatedTarget);schedule();});
  document.addEventListener('visibilitychange',schedule);
  reducedMotion.addEventListener('change',event=>{if(event.matches){userPaused=true;updatePlayback();schedule();}});
  updatePlayback();
  schedule();
})();
