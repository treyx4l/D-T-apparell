(function () {
  'use strict';

  const slides = [
    {
      title: 'Style That Speaks',
      subtitle: 'Discover premium apparel for every occasion',
      cta: 'Shop Collection',
      bg: 'linear-gradient(135deg, rgba(17,17,17,0.75), rgba(17,17,17,0.45)), url(https://cdn.mos.cms.futurecdn.net/whowhatwear/posts/296920/thrift-fashion-2021-296920-1639160576023-main.jpg)',
    },
    {
      title: 'New Season Arrivals',
      subtitle: 'Fresh fits for Men, Women & Kids',
      cta: 'Explore Now',
      bg: 'linear-gradient(135deg, rgba(17,17,17,0.75), rgba(17,17,17,0.45)), url(https://cdn.shopify.com/s/files/1/0275/7761/4381/files/summer-maisie-olive-floral-skirt-outfits-for-ladies.jpg?v=1749712085)',
    },
    {
      title: 'Elevate Your Wardrobe',
      subtitle: 'Quality fabrics. Modern cuts. Bold style.',
      cta: 'Shop Now',
      bg: 'linear-gradient(135deg, rgba(17,17,17,0.75), rgba(17,17,17,0.45)), url(https://images.unsplash.com/photo-1469334031218-e382a71b716b?w=1600&q=80)',
    },
  ];

  let current = 0;
  let timer;

  const hero = document.getElementById('heroCarousel');
  const dotsContainer = document.getElementById('heroDots');
  if (!hero) return;

  slides.forEach((slide, index) => {
    const el = document.createElement('div');
    el.className = 'hero-slide' + (index === 0 ? ' opacity-100' : ' opacity-0 pointer-events-none');
    el.style.backgroundImage = slide.bg;
    el.innerHTML = `
      <div class="mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8">
        <div class="max-w-xl text-white">
          <p class="mb-3 text-sm font-semibold uppercase tracking-widest text-accent">D&amp;T Apparels</p>
          <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">${slide.title}</h1>
          <p class="mt-4 text-lg text-gray-200">${slide.subtitle}</p>
          <a href="store.php" class="btn-primary mt-8">${slide.cta}</a>
        </div>
      </div>`;
    hero.appendChild(el);

    const dot = document.createElement('button');
    dot.type = 'button';
    dot.className = 'h-2.5 w-2.5 rounded-full transition ' + (index === 0 ? 'bg-accent w-8' : 'bg-white/50');
    dot.setAttribute('aria-label', `Go to slide ${index + 1}`);
    dot.addEventListener('click', () => goTo(index));
    dotsContainer?.appendChild(dot);
  });

  const slideEls = hero.querySelectorAll('.hero-slide');
  const dotEls = dotsContainer?.querySelectorAll('button') || [];

  function goTo(index) {
    current = index;
    slideEls.forEach((el, i) => {
      el.classList.toggle('opacity-100', i === index);
      el.classList.toggle('opacity-0', i !== index);
      el.classList.toggle('pointer-events-none', i !== index);
    });
    dotEls.forEach((dot, i) => {
      dot.classList.toggle('bg-accent', i === index);
      dot.classList.toggle('w-8', i === index);
      dot.classList.toggle('bg-white/50', i !== index);
      dot.classList.toggle('w-2.5', i !== index);
    });
  }

  function next() {
    goTo((current + 1) % slides.length);
  }

  timer = setInterval(next, 5000);
  hero.addEventListener('mouseenter', () => clearInterval(timer));
  hero.addEventListener('mouseleave', () => { timer = setInterval(next, 5000); });
})();
