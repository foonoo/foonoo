<?php


namespace foonoo\events;


use foonoo\sites\AssetPipeline;

/**
 * This event is dispatched when the asset pipeline for the site is ready.
 * Listeners of this event can access the asset pipeline and inject their own assets. Scripts and stylesheets added
 * through this event are eventually built and added to the site's final asset bundle.
 *
 * @package foonoo\events
 */
class AssetPipelineReady
{
    private $pipeline;

    public function __construct(AssetPipeline  $pipeline) {
        $this->pipeline = $pipeline;
    }

    public function getAssetPipeline() : AssetPipeline {
        return $this->pipeline;
    }
}