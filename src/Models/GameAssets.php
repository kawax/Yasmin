<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Models;

/**
 * Something someone needs (or wants) in games. Or in Rich Presence.
 *
 * @property \CharlotteDunois\Yasmin\Models\Game  $game        The game which these assets belong to.
 * @property string|null                          $largeImage  The ID of the large image, or null.
 * @property string|null                          $largeText   The text of the large image, or null.
 * @property string|null                          $smallImage  The ID of the small image, or null.
 * @property string|null                          $smallText   The text of the small image, or null.
 */
class GameAssets extends ClientBase {
    protected $game;
    protected $largeImage;
    protected $largeText;
    protected $smallImage;
    protected $smallText;
    
    /**
     * The manual creation of such an object is discouraged. There may be an easy and safe way to create such an object in the future.
     * @param \CharlotteDunois\Yasmin\Client       $client  The client this object is for.
     * @param \CharlotteDunois\Yasmin\Models\Game  $game    The game instance
     * @param array                                $assets  An array containing the presence data.
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Models\Game $game, array $assets) {
        parent::__construct($client);
        $this->game = $game;
        
        $this->largeImage = $assets['large_image'] ?? null;
        $this->largeText = $assets['large_text'] ?? null;
        $this->smallImage = $assets['small_image'] ?? null;
        $this->smallText = $assets['small_text'] ?? null;
    }
    
    /**
     * @inheritDoc
     *
     * @throws \Exception
     * @internal
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'streaming':
                return (bool) ($this->type === 1);
            break;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Returns the URL of the large image.
     * @param int|null  $size  Any powers of 2.
     * @return string|null
     */
    function getLargeImageURL(int $size = null) {
        if($this->largeImage) {
            return \CharlotteDunois\Yasmin\Constants::CDN['url'].\CharlotteDunois\Yasmin\Constants::format(\CharlotteDunois\Yasmin\Constants::CDN['appassets'], $this->game->applicationID, $this->largeImage).(!empty($size) ? '?size='.$size : '');
        }
        
        return null;
    }
    
    /**
     * Returns the URL of the small image.
     * @param int|null  $size  Any powers of 2.
     * @return string|null
     */
    function getSmallImageURL(int $size = null) {
        if($this->smallImage) {
            return \CharlotteDunois\Yasmin\Constants::CDN['url'].\CharlotteDunois\Yasmin\Constants::format(\CharlotteDunois\Yasmin\Constants::CDN['appassets'], $this->game->applicationID, $this->smallImage).(!empty($size) ? '?size='.$size : '');
        }
        
        return null;
    }
    
    /**
     * @internal
     */
    function jsonSerialize() {
        return array(
            'large_image' => $this->largeImage,
            'large_text' => $this->largeText,
            'small_image' => $this->smallImage,
            'small_text' => $this->smallText
        );
    }
}
