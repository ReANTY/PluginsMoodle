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
                        if (stage === 'post') {
                            var $quizPopup = $('.acmls-quiz-motivation-popup');
                            if ($quizPopup.length) {
                                $quizPopup.addClass('is-visible');
                            }
                        }
                    }, 250);
                }).fail(function(err) {
                    $submit.prop('disabled', false);
                    Notification.exception(err);
                });
            });

            // Handle dismiss post-quiz/assignment motivation.
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
                }, 250);
            });
        }
    };
});
