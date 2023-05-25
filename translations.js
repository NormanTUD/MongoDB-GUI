// Get the language from the query string parameter 'lang'
const urlParams = new URLSearchParams(window.location.search);
let lang = urlParams.get('lang') || 'en'; // Default language is English

function setLang (l) {
	lang = l;
	updateTranslations();
}

// Function to update the translation of elements
function updateTranslations() {
	const elements = document.querySelectorAll('[class^="TRANSLATEME_"]');
	elements.forEach((element) => {
		const translationKey = element.classList[0].substring(12);
		const translation = language[lang][translationKey];
		if(translation) {
			element.textContent = translation;
		} else {
			alert("Could not translate " + translationKey + " to " + lang);
		}
	});
}

// Update translations on initial page load
updateTranslations();

// Update translations when language selector links are clicked
var languageSelectors = $(".language-selector").find("span")
Array.from(languageSelectors).forEach((selector) => {
	selector.addEventListener('click', function (event) {
		event.preventDefault();
	});
});

// Update translations when language is changed via URL parameter
window.addEventListener('popstate', function () {
	const newLang = urlParams.get('lang') || 'en';
	if (newLang !== lang) {
		lang = newLang;
		updateTranslations();
	}
});
