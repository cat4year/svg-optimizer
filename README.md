# SVG use optimizer

### Problem
The page is bloated in weight for the client and visually for the developer due to the large number of identical svg's

### Solution
Collect all the svg into a sprite and use the link. The sprite should contain only those svg use elements that are 
needed on this page.

### How to Use
You need a svg sprite for the basic functionality to work (SvgStorage class).
We can build this sprite in development mode using SvgSpriteBuilder class.

#### SvgSpriteBuilder class
Use to build a sprite of all necessary svg files so as not to waste the user's time when prompted.
It is possible to build from a directory recursively or from a like html file.

There are two ways to build: from a file or from directory files

For example:
```php
$svgSpriteBuilder = new \SvgReuser\SvgSpriteBuilder('sprite.svg');
$svgSpriteBuilder->buildSpriteFromDirectory('./directory');
$svgSpriteBuilder->buildSpriteFromFile('./dirty-svgs.html');
```

#### SvgStorage class
Core Functionality Class. It is used to optimally connect svg use elements and only the svg symbols needed for these calls will be in the sprite

For example:
```php
$storage = new \SvgReuser\SvgStorage();
//this method must be loaded before the showSvg methods are called
$storage->loadSprite('./sprite.svg');

$storage->showSvg('logoId', 'logo__class');
$storage->showSvg('heart');
$storage->showSvg('cartBig', 'cart__big');

//this method should be called after all calls to showSvg. 
$storage->showSprite();
```

For work need implementation of SvgSanizer with method sanitize().
Can use:

> enshrined/svg-sanitize but have trouble with psr-4

> rhukster/dom-sanitizer but minify, or pretty need test and fix