(() => {
  'use strict';
  const form=document.getElementById('email-composer');
  if(!form) return;
  const review=document.getElementById('message-review');
  const text=document.getElementById('prepared-text');
  const status=document.getElementById('copy-status');
  form.addEventListener('submit',event=>event.preventDefault());
  document.getElementById('prepare-message').addEventListener('click',()=>{
    if(!form.reportValidity())return;
    const fields=new FormData(form);
    const name=String(fields.get('name')).trim();
    const email=String(fields.get('email')).trim();
    const topic=String(fields.get('subject')).trim();
    const message=String(fields.get('message')).trim();
    const subject=form.dataset.kind+(topic?' — '+topic:'');
    const body='Name: '+name+'\nEmail: '+email+(topic?'\nSubject: '+topic:'')+'\n\n'+message;
    text.value=body;
    document.getElementById('open-email').href='mailto:'+form.dataset.recipient+'?subject='+encodeURIComponent(subject)+'&body='+encodeURIComponent(body);
    review.hidden=false;status.textContent='';text.focus();
  });
  document.getElementById('copy-message').addEventListener('click',async()=>{
    try { await navigator.clipboard.writeText(text.value);status.textContent='Message copied. Paste it into your email application when you are ready.'; }
    catch {text.focus();text.select();status.textContent='Select and copy the prepared message, then paste it into your email application.';}
  });
})();
