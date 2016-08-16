<?php
/*
Plugin Name: HM Footnotes
Version: 0.1
Description: Enables footnote via shortcode
Author: Martin Wecke
Author URI: http://martinwecke.de/
GitHub Plugin URI: https://github.com/hatsumatsu/hm-footnotes
GitHub Branch: master
*/


/**
 * HMAttachments
 */
class HMFootnotes {
    protected $settings;
    public static $instance;

    public function __construct() {
        // i11n
        add_action( 'init', array( $this, 'loadI88n' ) );

        // load settings
        add_action( 'after_setup_theme', array( $this, 'loadSettings' ) );

        // parse footnotes
        add_action( 'the_content', array( $this, 'parseFootnotes' ), 99 );
    }


    /**
     * i11n
     */
    public function loadI88n() {
        load_plugin_textdomain( 'hm-footnotes', '/wp-content/plugins/hm-footnotes/languages/' );
    }


    /**
     * Load settings from filter 'hm-attachments/settings'
     */
    public function loadSettings() {
        // default settings
        $this->settings = array(
            'append_to_content' => true,
            'labels' => array(
                'endnotes' => __( 'Footnotes', 'hm-footnotes' )
            )
        );

        // apply custom settings
        $this->settings = apply_filters( 'hm-footnotes/settings', $this->settings );
    }


    /**
     * Extract footnotes from post content and save them in the global $post
     *
     * Replaces shortcode [1. Lorem Ipsum] with
     *
     *   <span class="footnote-inline" data-footnote-id="1" data-footnote-text="Lorem Ipsum">
     *     <a href="#footnotes/1" name="footnotes/inline/1" class="footnote-inline-number">
     *       1
     *     </a>
     *     <span class="footnote-tooltip">
     *       <span class="footnote-tooltip-inner">
     *         <span class="footnote-tooltip-number">
     *           1
     *         </span>
     *         Lorem Ipsum
     *       </span>
     *     </span>
     *   </span>
     *
     * Adds list of footnotes with back links to the end of the content
     *
     * Regex http://stackoverflow.com/q/32861047/2799523
     * @param $content post content
     * @return string post content
     */
    public function parseFootnotes( $content ) {
        global $post;

        $footnotes = array();

        // replace shortcode
        $content = preg_replace_callback(
            // '/\[([0-9]+)\.\s([^\]]+)\]/',
            '~\[ (?(R)|(\d+)\.\s) ( [^][]*+ (?:(?R) [^][]*)*+ ) ]~x',
            function( $matches ) use ( &$footnotes )  {
                // save hits
                $footnotes[$matches[1]] = $matches[2];

                return '<span class="footnote-inline" data-footnote-id="' . esc_attr( $matches[1] ) . '" data-footnote-text="' . esc_attr( $matches[2] ) . '"><a href="#footnotes--' . esc_attr( $matches[1] ) . '" name="footnotes-inline--' . esc_attr( $matches[1] ) . '" class="footnote-inline-number">' . $matches[1] . '</a><span class="footnote-tooltip"><span class="footnote-tooltip-inner"><span class="footnote-tooltip-number">' . $matches[1] . '</span>' . $matches[2] . '</span></span></span>';
            },
            $content
        );

        $post->hm_footnotes = $footnotes;

        if( $footnotes && $this->settings['append_to_content'] ) {
            $content .= $this->getEndnotes( $footnotes );
        }

        return $content;
    }


    /**
     * Get endnotes markup by footnote array
     * @param  array $footnotes array of [index] => [text] pairs
     * @return string HTML markup
     */
    public function getEndnotes( $footnotes = array() ) {
        global $post;

        if( !$this->settings ) {
            $this->loadSettings();
        }

        if( !$footnotes ) {
            $footnotes = $post->hm_footnotes;
        }

        if( !$footnotes ) {
            return false;
        }

        $html = '';

        if( count( $footnotes ) ) {
            $html = '';
            $html .= '<ul class="footnotes">';

            $html .= '<h4>';
            $html .= $this->settings['labels']['endnotes'];
            $html .= '</h4>';

            foreach( $footnotes as $index => $text ) {
                $html .= '<li class="footnote">';
                $html .= '<a href="#footnotes-inline--' . esc_attr( $index ) . '" name="footnotes--' . esc_attr( $index ) . '" class="footnote-number">';
                $html .= $index;
                $html .= '</a>';
                $html .= '<span class="footnote-text">';
                $html .= $text;
                $html .= '</span>';
                $html .= '</li>';
            }
            $html .= '</ul>';
        }

        return $html;
    }


    /**
     * Get instance
     * @return HMFootnotes current plugin's instance
     */
    public static function get() {
        if( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}

new HMFootnotes();
