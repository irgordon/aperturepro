// Ensure SignaturePad is available if used in theme/portal templates
(function(){
  if (typeof window.SignaturePad === 'undefined') {
    console.warn('SignaturePad library not loaded. Include it in the front-end.');
    return;
  }
  const canvas = document.getElementById('ap-signature-pad');
  const btn = document.getElementById('ap-signature-save');
  if (!canvas || !btn) return;
  const pad = new window.SignaturePad(canvas);
  btn.addEventListener('click', () => {
    const dataUrl = pad.toDataURL('image/png');
    console.log('Signature captured', dataUrl);
  });
})();
