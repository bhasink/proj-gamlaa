<header>
    <a href="{{ url('/') }}" class="head_logg">
        <img src="{{ asset('images/footer-logo.png') }}" alt="Gamlaa" class="white-logo">
        <img src="{{ asset('images/gamla-black-logo.png') }}" alt="Gamlaa" class="black-logo">
    </a>
    <div class="head_menu">
        <nav>
            <a href="#">Who we are</a>
            <a href="#">Our Solutions</a>
            <a href="#">Our Work</a>
            <a href="{{ route('design-inspiration.index') }}">What Inspires Us</a>
            <a href="#">For Architects</a>
            <a href="#">Resources</a>
            <a href="#">Experience Center</a>
        </nav>
        <a href="#" class="contact_btn">Contact Us</a>
        <div class="mb_menu_btn ham_btn">
            <span class="line1"></span>
            <span class="line2"></span>
            <span class="line3"></span>
        </div>
    </div>
</header>
