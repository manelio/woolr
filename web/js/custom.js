;(function() {

var
  $navbar = $(".navbar:first"),
  fixedClass = "navbar-fixed-top",
  headerHeight = $('header').outerHeight(true),
  navbarHeight = $navbar.outerHeight(true),
  $next = $navbar.next()
;

headerHeight = $navbar.offset().top;
//if (!headerHeight) return;

$(window).scroll(function() {
  if( $(this).scrollTop() > headerHeight ) {
    $navbar.addClass(fixedClass);
    $next.css({paddingTop: navbarHeight+'px'});
  } else {
    $navbar.removeClass(fixedClass);
    $next.css({paddingTop: 0});
  }
});

})();
