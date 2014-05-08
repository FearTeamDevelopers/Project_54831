/**
*	Site-specific configuration settings for Highslide JS
*/
hs.graphicsDir = '/public/images/graphics/';
hs.outlineType = 'custom';
hs.captionEval = 'this.a.title';
hs.registerOverlay({
	html: '<div class="closebutton" onclick="return hs.close(this)" title="Zavřít"></div>',
	position: 'top right',
	useOnHtml: true,
	fade: 2 // fading the semi-transparent overlay looks bad in IE
});



// Add the slideshow controller
hs.addSlideshow({
	slideshowGroup: ['group1', 'group2', 'group3', 'group4', 'group5', 'group6', 'group7', 'group8'],
	interval: 5000,
	repeat: false,
	useControls: false,
	thumbstrip: {
		mode: 'vertical',
		position: 'left',
		relativeTo: 'viewport',
                width: '75px'
	}

});

// Czech language strings
hs.lang = {
	cssDirection: 'ltr',
	loadingText: 'Načítá se...',
	loadingTitle: 'Klikněte pro zrušení',
	focusTitle: 'Klikněte pro přenesení do popředí',
	fullExpandTitle: 'Zvětšit na původní velikost',
	creditsText: '',
	creditsTitle: '',
	previousText: 'Předchozí',
	nextText: 'Další',
	moveText: 'Přesunout',
	closeText: 'Zavřít',
	closeTitle: 'Zavřít (esc)',
	resizeTitle: 'Změnit velikost',
	playText: 'Přehrát',
	playTitle: 'Přehrát slideshow (mezerník)',
	pauseText: 'Pozastavit',
	pauseTitle: 'Pozastavit slideshow (mezerník)',
	previousTitle: 'Předchozí (šipka vlevo)',
	nextTitle: 'Další (šipka vpravo)',
	moveTitle: 'Přesunout',
	fullExpandText: 'Plná velikost',
	number: 'Image %1 of %2',
	restoreTitle: 'Klikněte pro zavření obrázku, klikněte a táhněte pro jeho přesunutí. Použijte šipky na klávesnici pro přesun na další a předchozí.'
};

// gallery config object
var configbio = {
	slideshowGroup: 'group1',
	transitions: ['expand', 'crossfade']
};

var configstyling = {
	slideshowGroup: 'group2',
	transitions: ['expand', 'crossfade']
};

var configdesign = {
	slideshowGroup: 'group3',
	transitions: ['expand', 'crossfade']
};

var confignews = {
	slideshowGroup: 'group4',
	transitions: ['expand', 'crossfade']
};

var configportfolio = {
	slideshowGroup: 'group5',
	transitions: ['expand', 'crossfade']
};

var configcollection = {
	slideshowGroup: 'group6',
	transitions: ['expand', 'crossfade']
};

var configsubcollection = {
	slideshowGroup: 'group7',
	transitions: ['expand', 'crossfade']
};