
function show(spin) {
  var spinner = document.getElementById('spinner');
  if (spin == "ok") {
    spinner.classList.remove('hidden');
  } else {
    spinner.classList.add('hidden');
  }
}
