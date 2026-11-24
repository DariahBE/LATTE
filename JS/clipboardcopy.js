
function fallbackCopyText(text) {
  const target = document.createElement('textarea');
  target.id = 'temp_copy_elem';
  target.value = text;
  target.setAttribute('readonly', '');
  target.style.position = 'fixed';
  target.style.top = '0';
  target.style.left = '-9999px';
  target.style.width = '1px';
  target.style.height = '1px';
  target.style.opacity = '0';
  document.body.appendChild(target);
  target.focus();
  target.select();
  target.setSelectionRange(0, text.length);

  let copied = false;
  try {
    copied = document.execCommand('copy');
  } catch (e) {
    copied = false;
  }
  document.body.removeChild(target);
  return copied;
}

function showCopyStatus(idOfElement, idOfOkay, message) {
  if (!idOfOkay) return;
  const okEl = document.getElementById(idOfOkay);
  if (!okEl) return;
  okEl.innerText = message;
  toggle(idOfElement, idOfOkay);
  setTimeout(function () {
    okEl.innerText = '';
    toggle(idOfOkay, idOfElement);
  }, 1500);
}

async function clippy(idOfElement, idOfOkay) {
  const source = document.getElementById(idOfElement);
  if (!source) return;
  const textToCopy = (source.innerText || source.textContent || '').trim();
  if (!textToCopy) return;

  let copied = false;
  if (navigator.clipboard && window.isSecureContext) {
    try {
      await navigator.clipboard.writeText(textToCopy);
      copied = true;
    } catch (e) {
      copied = false;
    }
  }

  if (!copied) {
    copied = fallbackCopyText(textToCopy);
  }

  showCopyStatus(
    idOfElement,
    idOfOkay,
    copied ? 'URI has been copied.' : 'Copy failed; please copy manually.'
  );
}

function toggle(hide, show){
  document.getElementById(hide).classList.add('hidden');
  document.getElementById(show).classList.remove('hidden');
}
