<!--

function saveScroll()
{
  var x = 0, y = 0;

  if ( typeof( window.pageYOffset ) == 'number' ) {
    //Netscape compliant
    y = window.pageYOffset;
    x = window.pageXOffset;
  } 
  else if ( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
    //DOM compliant
    y = document.body.scrollTop;
    x = document.body.scrollLeft;
  } 
  else if ( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
    //IE6 standards compliant mode
    y = document.documentElement.scrollTop;
    x = document.documentElement.scrollLeft;
  }

  document.wizard.scroll.value = x + "_" + y;
}

function loadScroll()
{
  if ( ! window.scrollTo) return;

  var xy = getScroll();

  if ( ! xy ) return;

  var ar = xy.split("_");

  if ( ar.length == 2 ) scrollTo( parseInt(ar[0]), parseInt(ar[1]) );
}

function confirmRemoval()
{
  var agree = confirm("Are you sure you want to remove this resource?");

  if (agree)
    return true ;
  else
    return false ;
}

// -->
