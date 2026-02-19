/*===============================================================================*/
/* START (Optimized JS Template)                                                 */
/*===============================================================================*/

document.addEventListener('DOMContentLoaded', () => {

  const containers = document.getElementsByClassName('hero-two-section');
  if (!containers.length) return;
  startJs(containers[0]);

});

// startJs
async function startJs(container) {
  //Get col2
  //opacity 0 col2
  const col_2 = container.querySelector('.col-2');
  const img = col_2?.querySelector('img');
  
  //put the image to container background
  if (!container || !img) {
    console.warn('Hero or image not found');
    return;
  }

  // get image source
  const imgSrc = img.currentSrc || img.src;

  // Setear background
  container.style.backgroundImage = `url(${imgSrc})`;
  //img.style.display = 'none';
}
