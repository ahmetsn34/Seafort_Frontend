<?php
declare(strict_types=1);
$heroSlides = function_exists('sf_collection') ? sf_collection('slides', true) : [];
if (!$heroSlides) {
    $heroSlides = [[
        'title' => 'Marine & offshore repair & maintenance.',
        'description' => 'Steel repairs, protective coatings, equipment installation and underwater services. Practical support for your vessel and offshore projects, defined around the work you need.',
        'eyebrow' => 'MARINE & OFFSHORE SERVICES',
        'cover' => '/assets/project/marine-offshore-worksite.webp',
        'cover_alt' => 'Offshore vessel alongside a worksite with scaffolding, equipment and personnel',
        'link_label' => 'Discuss Your Project',
        'link_href' => '/get-a-quote/',
    ]];
}
$heroSlideCount = count($heroSlides);
?>
<section class="hero-carousel" id="hero-carousel" role="region" aria-roledescription="carousel" aria-label="Seafort services" tabindex="0">
    <h1 class="carousel-sr-only"><?=h($homeContent['meta']['hero_title'])?></h1>
    <div class="carousel-stage">
        <?php foreach ($heroSlides as $slideIndex => $heroSlide):
            $slideCover = sf_cover_url((string)($heroSlide['cover'] ?? '')) ?: '/assets/project/marine-offshore-worksite.webp';
            $slideHref = (string)($heroSlide['link_href'] ?? '/get-a-quote/');
            $slideHasCta = trim((string)($heroSlide['link_label'] ?? '')) !== '' && trim((string)($heroSlide['link_href'] ?? '')) !== '';
            if (!str_starts_with($slideHref, '/') || str_starts_with($slideHref, '//') || preg_match('/[\x00-\x20\\\\]/', $slideHref)) $slideHref = '/get-a-quote/';
        ?>
        <div class="carousel-slide" data-slide role="group" aria-roledescription="slide" aria-label="<?=h((string)($slideIndex + 1))?> of <?=h((string)$heroSlideCount)?>" <?=$slideIndex > 0 ? 'hidden inert' : ''?>>
            <img class="carousel-image" src="<?=h($slideCover)?>" width="1680" height="1077" alt="<?=h($heroSlide['cover_alt'] ?? '')?>" <?=$slideIndex === 0 ? 'fetchpriority="high"' : 'loading="lazy" fetchpriority="low"'?>>
            <div class="carousel-shade" aria-hidden="true"></div>
            <div class="wrap carousel-content">
                <div class="carousel-copy">
                    <p class="eyebrow eyebrow-light carousel-eyebrow"><span aria-hidden="true"></span><?=h($heroSlide['eyebrow'] ?? 'MARINE & OFFSHORE SERVICES')?></p>
                    <h2 class="carousel-title"><?=h($heroSlide['title'] ?? '')?></h2>
                    <p class="carousel-description"><?=h($heroSlide['description'] ?? '')?></p>
                    <div class="carousel-actions">
                        <?php if ($slideHasCta): ?><a class="button" href="<?=h($slideHref)?>"><?=h($heroSlide['link_label'])?></a><?php endif; ?>
                        <a class="text-link light" href="#services"><?=h($homeContent['meta']['hero_link_label'])?></a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="wrap carousel-footer">
        <p class="carousel-positioning"><?=h($homeContent['meta']['hero_positioning'])?><br><strong><?=h($homeContent['meta']['hero_positioning_emphasis'])?></strong></p>
        <?php if ($heroSlideCount > 1): ?>
        <div class="carousel-controls" aria-label="Slideshow controls" hidden>
            <div class="carousel-pagination" role="group" aria-label="Choose a slide">
                <?php foreach ($heroSlides as $dotIndex => $dotSlide): ?>
                <button class="carousel-dot" type="button" data-go-to="<?=$dotIndex?>" aria-label="Show slide <?=$dotIndex+1?>: <?=h($dotSlide['title'] ?? '')?>" <?=$dotIndex === 0 ? 'aria-current="true"' : ''?>><span></span></button>
                <?php endforeach; ?>
            </div>
            <div class="carousel-counter" aria-hidden="true"><span data-current>01</span><span class="counter-divider">/</span><span><?=str_pad((string)$heroSlideCount,2,'0',STR_PAD_LEFT)?></span></div>
            <div class="carousel-arrows">
                <button type="button" class="carousel-arrow" data-previous aria-label="Previous slide"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5m6-6-6 6 6 6"/></svg></button>
                <button type="button" class="carousel-arrow" data-next aria-label="Next slide"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></button>
            </div>
            <button type="button" class="carousel-playback" data-playback aria-label="Pause slideshow"><svg class="playback-pause" viewBox="0 0 16 16" aria-hidden="true"><path d="M5 3v10M11 3v10"/></svg><svg class="playback-play" viewBox="0 0 16 16" aria-hidden="true"><path d="m5 3 8 5-8 5Z"/></svg><span data-playback-label>Pause</span></button>
        </div>
        <?php else: ?>
        <a class="carousel-scroll" href="#services"><span>OUR SERVICES</span></a>
        <?php endif; ?>
    </div>
    <p class="carousel-sr-only" data-slide-announcement aria-live="polite" aria-atomic="true"></p>
</section>
