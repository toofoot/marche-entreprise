$(function(){
	var timer = null;
	
	// AVEC ANIMATION
    $(".liMenu").hover(function(){
    	clearTimeout(timer);
    	if($(this).has('.UlUnder')){
	    	$('.ulUnder').not($('.ulUnder',this)).stop(true,true).fadeOut();
	    	$('.ulUnder',this).stop(true,true).fadeIn();
    	}
    },function(){
    	var elt = this;
    	timer = setTimeout(function(){
    		$('.ulUnder',elt).stop(true,true).fadeOut();
    	},200);
    });
	
    // SANS ANIMATION
    /*$(".liMenu").hover(function(){
    	clearTimeout(timer);
    	if($(this).has('.UlUnder')){
	    	$('.ulUnder').not($('.ulUnder',this)).stop(true,true).hide();
	    	$('.ulUnder',this).stop(true,true).show();
    	}
    },function(){
    	var elt = this;
    	timer = setTimeout(function(){
    		$('.ulUnder',elt).stop(true,true).hide();
    	},200);
    });*/
    
    $('.colorbox').colorbox();
    
    $('.delete').click(function(){
    	return confirm('Etes vous sur de vouloir supprimer ce menu et son contenu ?');
    });
    
    $('.unclickable').click(function(){
    	return false;
    });
});