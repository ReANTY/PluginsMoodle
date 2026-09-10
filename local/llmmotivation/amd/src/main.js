// This file is part of Moodle - http://moodle.org/

define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    'use strict';

    function submitMotivationFeedback(args) {
        return Ajax.call([{
            methodname: 'local_llmmotivation_submit_feedback',
            args: args
        }])[0];
    }

    function recordInteraction(userid, courseid, interactionType, data) {
        return Ajax.call([{
            methodname: 'local_llmmotivation_record_interaction',
            args: {
                userid: userid,
                courseid: courseid,
                interaction_type: interactionType,
                data: JSON.stringify(data || {})
            }
        }])[0];
    }

    return {
        init: function(userid, courseid) {
            // Handle submit emotion check-in form.
            $(document).on('submit', '.acmls-emotion-checkin-form', function(e) {
                e.preventDefault();

                var $form = $(this);
                var $popup = $form.closest('.acmls-motivation-popup');
                
                var e1_name = 'acmls-e1-' + userid;
                var e2_name = 'acmls-e2-' + userid;
                var e3_name = 'acmls-e3-' + userid;

                var $e1_selected = $form.find('input[name="' + e1_name + '"]:checked');
                var $e2_selected = $form.find('input[name="' + e2_name + '"]:checked');
                var $e3_selected = $form.find('input[name="' + e3_name + '"]:checked');
                
                var $required = $form.find('.acmls-motivation-popup__required');
                var $submit = $form.find('.acmls-emotion-popup__submit');

                if (!$e1_selected.length || !$e2_selected.length || !$e3_selected.length) {
                    $required.removeClass('d-none');
                    return;
                }

                $required.addClass('d-none');
                $submit.prop('disabled', true);

                var category = $popup.data('category') || 'checkin';
                var source = $popup.data('source') || 'initial_checkin';
                var stage = $popup.data('stage') || 'pre';
                var secnum = $popup.data('section') || 1;
                var msgContent = (stage === 'post')
                    ? ('Evaluasi Emosi Akhir Minggu ' + secnum)
                    : ('Check-in Kesiapan Emosi Awal Minggu ' + secnum);

                submitMotivationFeedback({
                    userid: userid,
                    courseid: courseid,
                    sentenceid: 0,
                    category: category,
                    source: source,
                    message_content: msgContent,
                    e1: Number($e1_selected.val() || 0),
                    e2: Number($e2_selected.val() || 0),
                    e3: Number($e3_selected.val() || 0),
                    reflection_note: $form.find('textarea[name="reflection_note"]').val() || ''
                }).done(function() {
                    $popup.removeClass('is-visible').addClass('is-submitted');
                    window.setTimeout(function() {
                        $popup.remove();
                    }, 250);
                }).fail(function(err) {
                    $submit.prop('disabled', false);
                    Notification.exception(err);
                });
            });

            // Handle dismiss post-quiz/assignment motivation and transition to final emotion check-in.
            $(document).on('click', '.acmls-quiz-motivation__dismiss', function(e) {
                e.preventDefault();
                var $popup = $(this).closest('.acmls-quiz-motivation-popup');
                var recordId = Number($popup.data('recordid') || 0);

                recordInteraction(userid, courseid, 'quiz_motivation_dismissed', {
                    recordid: recordId
                });

                $popup.removeClass('is-visible').addClass('is-submitted');
                window.setTimeout(function() {
                    $popup.remove();
                    // Transition to the final emotion check-in form if present.
                    var $postEmotionPopup = $('.acmls-emotion-popup[data-stage="post"]');
                    if ($postEmotionPopup.length) {
                        $postEmotionPopup.addClass('is-visible');
                    }
                }, 250);
            });

            // Handle clicking next section link on course view page to trigger its pre-checkin if ready
            $(document).on('click', '[data-for="section_title"], .courseindex-link, .section-item a, .sectionname a', function() {
                var href = $(this).attr('href') || '';
                var match = href.match(/section=(\d+)|#section-(\d+)/);
                if (match) {
                    var secNum = parseInt(match[1] || match[2], 10);
                    var $prePopup = $('.acmls-emotion-popup[data-stage="pre"][data-section="' + secNum + '"]');
                    if ($prePopup.length && !$prePopup.hasClass('is-visible') && !$prePopup.hasClass('is-submitted')) {
                        $prePopup.addClass('is-visible');
                    }
                }
            });
        }
    };
});
