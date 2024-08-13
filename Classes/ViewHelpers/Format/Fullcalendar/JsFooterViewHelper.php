<?php
namespace Slub\SlubEvents\ViewHelpers\Format\Fullcalendar;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Alexander Bigga <typo3@slub-dresden.de>, SLUB Dresden
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Add Fullcalendar specific JS code
 *
 * = Examples =
 *
 *
 * @api
 * @scope prototype
 */
class JsFooterViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initialize arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('categories', 'array', 'Categories', true);
        $this->registerArgument('settings', 'array', 'Settings', true);
        $this->registerArgument('link', 'string', 'Link', true);
    }


   /**
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $categories = $arguments['categories'];
        $settings = $arguments['settings'];
        $link = $arguments['link'];

        $js1 = '';

        $js1 .= "$(document).ready(function() {";
        $js1 .= "$('#calendar').fullCalendar({";
        if (!empty($settings['fullCalendarJS'])) {
            $js1 .= $settings['fullCalendarJS'];
        }

        $js1 .= "events: {
                    url: '".$link."',
                    data: function() {
                        var eventurl = '';
                        var disurl = '';
                        $('.slubevents-category input:checked').each(function() {
                            var cal = $(this).attr('id').split(\"-\")[2];
                            eventurl = eventurl + ',' + cal;
                        });
                        $('.slubevents-discipline input:checked').each(function() {
                            var cal = $(this).attr('id').split(\"-\")[2];
                            disurl = disurl + ',' + cal;
                        });
                        return {
                            categories: eventurl,
                            disciplines: disurl,
                            link: '" . urlencode($link) . "',
                            detailPid: '" . $settings['pidDetails'] . "'
                        };
                    }
            },";
        // add event name to title attribute on mouseover
        $js1 .= "eventMouseover: function(event, jsEvent, view) {
                            $(jsEvent.target).attr('title', moment(event.start).format('LT') + ' - ' + moment(event.end).format('LT') + ' ' + event.title);
                    },";

        $js1 .= "eventRender: function(event, element, view) {
                        if (view.name === 'agendaDay' && event.freePlaces != '0') {
                            element.find('.fc-event-title')
                                .after('<div class=\"fc-event-freeplaces\">' + event.freePlaces + '</div>');
                        }
                    },";
        // show/hide div #loading
        $js1 .= "loading: function(bool) {
                        if (bool) {
                            $('#loading').show();
                        } else {
                            $('#loading').hide();
                        }
                    },";

        // close fullCalendar()
        $js1 .= '});';
        // close $(document).ready()
        $js1 .= '});';

        /** @var $pageRenderer PageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addJsFooterInlineCode('js-slub-fullcalendar-config', $js1);

        if (empty($settings['fullCalendarJS'])) {
            $pageRenderer->addJsFooterLibrary('js-slub-fullcalendar-init', 'typo3conf/ext/slub_events/Resources/Public/Js/slub-events-fullcalendar-init.js');
        }
    }
}
