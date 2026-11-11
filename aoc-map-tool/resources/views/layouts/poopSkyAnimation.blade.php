<?php
use App\Helpers\LD;
?>
@if (LD::checkFeatureFlag('poop-sky-animation'))
<style>
.skyPoopAnimation {
    display: none;
    justify-content: space-around;
    position: fixed;
    width: 100%;
    height: 100%;
    overflow: hidden;
    margin: auto;
    background-size: 100% 100%;
    animation: sky 50s ease infinite;
    border-radius: 10px;
    z-index: 50000;
    top: -10%;
}

.emoji {
    display: block;
    position: relative;
    top: -10px;
    animation: rain linear infinite;
    transform: translate3d(0, 0, 0);
}

.poo1 {
    animation-duration: 10s;
}

.poo2 {
    animation-duration: 15s;
}

.poo3 {
    animation-duration: 5s;
}

.poo4 {
    animation-duration: 20s;
}

.poo5 {
    animation-duration: 25s;
}

.poo6 {
    animation-duration: 30s;
}

.poo7 {
    animation-duration: 10s;
}

.poo8 {
    animation-duration: 7s;
}

.poo9 {
    animation-duration: 30s;
}

.poo10 {
    animation-duration: 30s;
}

.poo11 {
    animation-duration: 13s;
}

.poo12 {
    animation-duration: 10s;
}

.poo13 {
    animation-duration: 20s;
}

.poo14 {
    animation-duration: 23s;
}

.poo15 {
    animation-duration: 12s;
}

@keyframes rain {
    from {
        top: 0;
    }

    to {
        top: 100%;
    }
}
</style>
<div class="skyPoopAnimation" id="skyPoopAnimation">
    <div class="emoji poo1">💩</div>
    <div class="emoji poo2">💩</div>
    <div class="emoji poo3">💩</div>
    <div class="emoji poo4">💩</div>
    <div class="emoji poo2">💩</div>
    <div class="emoji poo5">💩</div>
    <div class="emoji poo4">💩</div>
    <div class="emoji poo6">💩</div>
    <div class="emoji poo7">💩</div>
    <div class="emoji poo6">💩</div>
    <div class="emoji poo4">💩</div>
    <div class="emoji poo8">💩</div>
    <div class="emoji poo6">💩</div>
    <div class="emoji poo9">💩</div>
    <div class="emoji poo10">💩</div>
    <div class="emoji poo11">💩</div>
    <div class="emoji poo15">💩</div>
    <div class="emoji poo10">💩</div>
    <div class="emoji poo9">💩</div>
    <div class="emoji poo12">💩</div>
    <div class="emoji poo13">💩</div>
    <div class="emoji poo14">💩</div>
    <div class="emoji poo13">💩</div>
    <div class="emoji poo15">💩</div>
</div>
<script>
function showPoopSkyAnimation() {
    $('#skyPoopAnimation').css({
        display: 'flex'
    });
    setTimeout(function() {
        $('#skyPoopAnimation').css({
            display: 'none'
        });
    }, 5000);
}
</script>
@endif
