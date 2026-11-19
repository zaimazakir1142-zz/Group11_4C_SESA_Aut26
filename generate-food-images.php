
<?php
set_time_limit(120);

$folder = __DIR__ . '/assets/images/foods/';

if (!is_dir($folder)) {
    mkdir($folder, 0777, true);
}

$foods = [
    ['dal-puri-filling', 'Dal Puri Filling', '#E9A23B'],
    ['sambar', 'Sambar', '#E97835'],
    ['white-bread-loaf', 'White Bread Loaf', '#F1D5A5'],
    ['brown-bread', 'Brown Bread', '#A96D42'],
    ['whole-wheat-bread', 'Whole Wheat Bread', '#C69A55'],
    ['garlic-bread', 'Garlic Bread', '#DDA451'],
    ['dinner-rolls', 'Dinner Rolls', '#D99A48'],
    ['bun', 'Bun', '#D99A48'],
    ['baguette', 'Baguette', '#C9873F'],
    ['naan', 'Naan', '#DDA451'],
    ['paratha-pack', 'Paratha Pack', '#E5B665'],
    ['roti', 'Roti', '#DDA451'],
    ['toast-slices', 'Toast Slices', '#DDA451'],
    ['paratha-with-egg', 'Paratha with Egg', '#E5B665'],
    ['luchi-alur-dom', 'Luchi Alur Dom', '#E9A23B'],
    ['omelette', 'Omelette', '#F2C34D'],
    ['boiled-eggs', 'Boiled Eggs', '#FFF0D6'],
    ['porridge', 'Porridge', '#E9D3A7'],
    ['oatmeal', 'Oatmeal', '#D8B77A'],
    ['cornflakes-pack', 'Cornflakes Pack', '#E7B15C'],
    ['pancakes', 'Pancakes', '#E9B765'],
    ['french-toast', 'French Toast', '#DDA451'],
    ['halwa-ruti', 'Halwa Ruti', '#D99A48'],
    ['breakfast-combo-box', 'Breakfast Combo Box', '#D7A66B'],
    ['samosa', 'Samosa', '#DFA044']
];

$created = 0;

foreach ($foods as $index => $food) {

    $id = $index + 76;
    $slug = $food[0];
    $name = $food[1];
    $color = $food[2];

    $safeName = htmlspecialchars(
        $name,
        ENT_QUOTES | ENT_XML1,
        'UTF-8'
    );

    // Food illustration
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg"
     width="200" height="200" viewBox="0 0 200 200">

<title>{$safeName}</title>

<rect width="200" height="200"
      rx="24" fill="#FFF8EF"/>

<circle cx="100" cy="90" r="72"
        fill="#FCE8CF"/>

<ellipse cx="100" cy="151"
         rx="60" ry="9"
         fill="#D8C4AA" opacity=".5"/>

<ellipse cx="100" cy="105"
         rx="53" ry="39"
         fill="#FFFFFF"
         stroke="#D8BFA0"
         stroke-width="4"/>

<ellipse cx="100" cy="99"
         rx="43" ry="29"
         fill="{$color}"/>

<circle cx="82" cy="91" r="5"
        fill="#FFF0D6"/>

<circle cx="112" cy="106" r="4"
        fill="#FFF0D6"/>

<circle cx="127" cy="88" r="4"
        fill="#FFF0D6"/>

<text x="100" y="175"
      text-anchor="middle"
      font-size="11"
      font-family="Arial"
      fill="#594536">{$safeName}</text>

</svg>
SVG;

    $filePath = $folder . $slug . '.svg';

    if (file_put_contents($filePath, $svg) !== false) {
        $created++;
    }
}

// ZIP creation
if (!class_exists('ZipArchive')) {
    exit('ERROR: PHP ZIP extension is disabled. Enable php_zip in php.ini.');
}

$zipPath = __DIR__ . '/replate-food-76-100.zip';

$zip = new ZipArchive();

if ($zip->open(
    $zipPath,
    ZipArchive::CREATE | ZipArchive::OVERWRITE
) === TRUE) {

    foreach ($foods as $food) {

        $filePath = $folder . $food[0] . '.svg';

        if (file_exists($filePath)) {
            $zip->addFile(
                $filePath,
                $food[0] . '.svg'
            );
        }
    }

    $zip->close();

    echo '<h2>RePlate Food Images Created!</h2>';
    echo '<p>Food IDs: 76 - 100</p>';
    echo '<p>SVG files created: ' . $created . '</p>';
    echo '<p><a href="replate-food-76-100.zip">Download ZIP</a></p>';

} else {
    echo 'ZIP creation failed!';
}
?>
