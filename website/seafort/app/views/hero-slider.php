<?php
declare(strict_types=1);
$heroSlides = sf_collection('slides', true);
if (!$heroSlides) {
    $heroSlides = [[
        'title' => 'Marine repair. Offshore support.',
        'cover' => '/assets/project/marine-offshore-worksite.webp',
        'cover_alt' => 'Offshore vessel alongside a worksite with scaffolding, equipment and personnel',
    ]];
}
$heroSlideCount = count($heroSlides);
$meta = $homeContent['meta'];
?>
<section class="hero-carousel" id="hero-carousel" role="region" aria-roledescription="carousel" aria-label="Seafort services" tabindex="0">
    <div class="carousel-stage">
        <?php foreach ($heroSlides as $slideIndex => $heroSlide):
            $cover = sf_cover_url((string)($heroSlide['cover'] ?? '')) ?: '/assets/project/marine-offshore-worksite.webp';
        ?>
        <div class="carousel-slide" data-slide role="group" aria-roledescription="slide" aria-label="<?=h((string)($slideIndex + 1))?> of <?=h((string)$heroSlideCount)?>" <?=$slideIndex > 0 ? 'hidden inert' : ''?>>
            <img class="carousel-image" src="<?=h($cover)?>" width="1680" height="1077" alt="<?=h($heroSlide['cover_alt'] ?? '')?>" <?=$slideIndex === 0 ? 'fetchpriority="high"' : 'loading="lazy" fetchpriority="low"'?>>
        </div>
        <?php endforeach; ?>
        <div class="carousel-shade" aria-hidden="true"></div>
    </div>
    <div class="wrap carousel-content">
        <div class="carousel-copy">
            <h1 class="carousel-title"><?=h($meta['hero_title'])?></h1>
            <p class="carousel-description"><?=h($meta['hero_intro'])?></p>
            <div class="carousel-actions">
                <a class="button" href="<?=h($meta['primary_href'])?>"><?=h($meta['primary_label'])?></a>
                <a class="text-link light" href="#services"><?=h($meta['hero_link_label'])?></a>
            </div>
        </div>
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
