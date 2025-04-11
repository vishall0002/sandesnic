import {
    tns
  } from 'tiny-slider/src/tiny-slider';
  var isSlider = document.getElementById('rewind');
  if (isSlider) {
  
    var slider = tns({
      container: "#rewind",
      autoWidth: true,
      items: 1,
      controls: false,
      rewind: true,
      swipeAngle: false,
      loop: true,
      autoplay: true,
      autoplayButtonOutput: false,
      speed: 400,
      nav: false
    });
  }