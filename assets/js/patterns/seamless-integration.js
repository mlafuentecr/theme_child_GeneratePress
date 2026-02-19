/*===============================================================================*/
/* START (Optimized JS Template)                                                 */
/*===============================================================================*/

document.addEventListener('DOMContentLoaded', () => {
const source = document.querySelector('.curve-text-to-replace');
  const target = document.querySelector('.curve-text');

  if (!source || !target) return;

  //0 tranasparent background
  target.style.backgroundColor = 'transparent';
  target.style.color = 'white';
  // 1. Capture text
  const text = source.textContent.trim();

  // 2. Remove original text element
  source.remove();

  // 3. Inject SVG with dynamic text
  target.innerHTML = `
    <svg
      viewBox="0 0 1000 500"
      width="100%"
      preserveAspectRatio="xMidYMid meet"
      xmlns="http://www.w3.org/2000/svg"
      style="max-width:1200px; display:block; margin:0 auto;"
    >
      <defs>
        <path
          id="seamless-curve"
          d="M 50 500 A 520 520 0 0 1 950 500"
          fill="none"
        />
      </defs>

      <text
        fill="#ffffff"
        font-size="28"
        font-weight="600"
        font-family="Arial, Helvetica, sans-serif"
        text-anchor="middle"
      >
        <textPath href="#seamless-curve" startOffset="50%">
          ${text}
        </textPath>
      </text>
    </svg>
  `;
});

