function removeUnwantedElements() {
  const progressBars = document.querySelectorAll(".reading-progress");
  progressBars.forEach((el) => el.remove());

  const tocElements = document.querySelectorAll(".table-of-contents");
  tocElements.forEach((el) => el.remove());

  const printBtns = document.querySelectorAll(".print-btn");
  printBtns.forEach((el) => el.remove());
}

document.addEventListener("DOMContentLoaded", () => {
  removeUnwantedElements();

  setTimeout(removeUnwantedElements, 500);
  setTimeout(removeUnwantedElements, 1000);
});
