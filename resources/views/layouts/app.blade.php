<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Gamlaa')</title>

    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=27">
    @stack('head-styles')

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100..900&display=swap" rel="stylesheet">
</head>
<body class="homepage">

    @include('partials.header')

    <main id="home">
        @yield('content')

        <div class="home_form">
            <div class="form_shape_maquee">
                <img src="{{ asset('images/green-leaf-marquee.png') }}" alt="">
                <img src="{{ asset('images/green-leaf-marquee.png') }}" alt="">
            </div>
        </div>
    </main>

    @include('partials.footer')

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.33/dist/lenis.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/ScrollTrigger.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/SplitText.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/MorphSVGPlugin.min.js"></script>

    <script>
        if (window.innerWidth <= 768) {
            document.body.classList.add("mobile_res");
            document.body.classList.remove("desktop_site");
        } else {
            document.body.classList.remove("mobile_res");
            document.body.classList.add("desktop_site");
        }

        gsap.registerPlugin(ScrollTrigger, SplitText, MorphSVGPlugin);

        (function () {
            const header = document.querySelector('header');
            if (!header) return;

            const isDesignInspirationPage = !!document.getElementById('diGallery');
            let lastScrollTop = window.pageYOffset || document.documentElement.scrollTop || 0;
            let ticking = false;

            function syncHeaderState() {
                const currentScroll = window.pageYOffset || document.documentElement.scrollTop || 0;
                header.classList.toggle('darkHeader', currentScroll >= 200);

                if (!isDesignInspirationPage) {
                    const scrollingUp = currentScroll < lastScrollTop;
                    document.body.classList.toggle('scrolling-up', scrollingUp);
                    document.body.classList.toggle('scrolling-down', !scrollingUp);
                } else {
                    document.body.classList.remove('scrolling-up', 'scrolling-down');
                }

                lastScrollTop = currentScroll;
                ticking = false;
            }

            window.addEventListener('scroll', function () {
                if (ticking) return;
                ticking = true;
                requestAnimationFrame(syncHeaderState);
            }, { passive: true });

            syncHeaderState();
        })();

        $('.mb_menu_btn').click(function () {
            $('header').toggleClass('open_menu');
            $(this).toggleClass('close_menu');
            $(this).toggleClass('ham_btn');
        });

        const isDesignInspirationPage = !!document.getElementById('diGallery');
        let lenis = null;
        if (!isDesignInspirationPage) {
            lenis = new Lenis({
                lerp: 0.08,
                wheelMultiplier: 1,
                smoothTouch: false,
                touchMultiplier: 1.5,
            });
            lenis.on('scroll', ScrollTrigger.update);
            gsap.ticker.add((time) => lenis.raf(time * 1000));
        }
        gsap.ticker.lagSmoothing(0);
        window.__lenis = lenis;

        gsap.set('.mobile_res header .head_menu nav',   { right: "-100%" });
        gsap.set('.mobile_res header .head_menu nav a', { x: 30, autoAlpha: 0 });

        const menuTl = gsap.timeline({ paused: true, reversed: true });
        menuTl.to('.mobile_res header .head_menu nav', {
            right: 0, duration: 0.6, ease: "expo.inOut", overwrite: "auto",
        })
        .to('.mobile_res header .head_menu nav a', {
            x: 0, autoAlpha: 1, duration: 0.4, ease: "circ.inOut",
            stagger: 0.08, overwrite: "auto",
        }, "-=0.2");

        $('.mobile_res header .mb_menu_btn.ham_btn').on('click', function () {
            menuTl.reversed() ? menuTl.play() : menuTl.reverse();
        });
        $('.mobile_res header .mb_menu_btn.close_menu').on('click', function () {
            menuTl.reverse();
        });

        gsap.set('.desktop_site header .head_menu a', { y: 40, opacity: 0 });
        gsap.set('header .head_logg', { scale: 0.2, opacity: 0 });

        gsap.to('header .head_logg', {
            scale: 1, opacity: 1, duration: 0.6, ease: "back.out(1.7)",
        });
        gsap.to('.desktop_site header .head_menu a', {
            y: 0, opacity: 1, duration: 0.6, stagger: 0.1, ease: "back.out(1.7)",
        });

        gsap.fromTo('header.open_menu .head_menu nav',
            { x: -100, opacity: 0 },
            { x: 0, opacity: 1, duration: 0.3 }
        );
        gsap.fromTo('header.open_menu .head_menu nav a',
            { y: 40, opacity: 0 },
            { y: 0, opacity: 1, duration: 0.3, stagger: 0.15 }
        );

        const footerTl = gsap.timeline({
            scrollTrigger: { trigger: 'footer', start: "top 80%", toggleActions: "play none none reverse" },
        });
        const footerText = new SplitText('.footer_text p', { type: 'lines' });

        footerTl.fromTo(footerText.lines,
            { y: 20, opacity: 0 },
            { y: 0,  opacity: 1, stagger: 0.03, duration: 0.4, ease: "power2.out" }
        )
        .fromTo('.footer_contact_info .f_info',
            { x: 20, opacity: 0 },
            { x: 0,  opacity: 1, stagger: 0.05, duration: 0.3, ease: "power2.out" },
            "-=0.3"
        )
        .fromTo('.footer_logo img',
            { y: 30, opacity: 0 },
            { y: 0,  opacity: 1, duration: 0.4, ease: "power2.out" },
            "-=0.2"
        )
        .fromTo('.footer_logo .footer_contact_btn',
            { y: 15, opacity: 0 },
            { y: 0,  opacity: 1, duration: 0.3, ease: "power2.out" },
            "-=0.2"
        )
        .fromTo('.footer_social .social_icon',
            { y: 15, opacity: 0 },
            { y: 0,  opacity: 1, stagger: 0.03, duration: 0.25, ease: "power2.out" },
            "-=0.2"
        )
        .fromTo('.footer_line',
            { scaleX: 0, transformOrigin: "left center" },
            { scaleX: 1, duration: 0.5, ease: "power2.out" },
            "-=0.15"
        )
        .fromTo('.footer_nav a',
            { y: 10, opacity: 0 },
            { y: 0,  opacity: 1, stagger: 0.03, duration: 0.25, ease: "power2.out" },
            "-=0.3"
        );
    </script>

    @stack('scripts')
</body>
</html>
