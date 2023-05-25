<div id="language_choser">
<?php
	$languageIcons = [
		'en' => '🇺🇸', // English
		'de' => '🇩🇪', // German
		'ja' => '🇯🇵', // Japanese
		'zh' => '🇨🇳', // Chinese
		// Add more language icons here
	];


        foreach ($language as $lang => $translations) {
            if (isset($languageIcons[$lang])) {
                echo '<span class="language-selector">';
		echo '<span onclick="setLang(\''.htmlentities($lang).'\')">' . $languageIcons[$lang] . '</span>';
                echo '</span>';
            }
        }
?>
</div>
