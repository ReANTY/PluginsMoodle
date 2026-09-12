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
                }).done(function(response) {
                    $popup.removeClass('is-visible').addClass('is-submitted');
                    window.setTimeout(function() {
                        $popup.remove();
                    }, 250);

                    // If this was post-section evaluation, reveal the AI Motivation Popup!
                    if (stage === 'post') {
                        var mot = null;
                        if (response && response.has_motivation && response.motivation_json) {
                            try {
                                mot = JSON.parse(response.motivation_json);
                            } catch (parseErr) {
                                mot = null;
                            }
                        }

                        var $quizMot = $('.acmls-quiz-motivation-popup');
                        if ($quizMot.length) {
                            if (mot) {
                                if (mot.recordid) {
                                    $quizMot.attr('data-recordid', mot.recordid);
                                }
                                if (mot.content) {
                                    $quizMot.find('.acmls-motivation-popup__message').text('"' + mot.content + '"');
                                }
                                if (mot.suggestion) {
                                    $quizMot.find('.acmls-motivation-popup__suggestion').text(mot.suggestion);
                                    $quizMot.find('.acmls-motivation-suggestion-box').removeClass('d-none').show();
                                }
                                if (mot.category_label) {
                                    $quizMot.find('.badge').text(mot.category_label);
                                }
                                if (mot.quizgrade_formatted) {
                                    $quizMot.find('.alert-info strong').text(mot.quizgrade_formatted);
                                }
                            }
                            window.setTimeout(function() {
                                $quizMot.addClass('is-visible');
                            }, 300);
                        } else if (mot && mot.content) {
                            var gradeHtml = mot.quizgrade_formatted
                                ? '<div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center"><strong>' + mot.quizgrade_formatted + '</strong></div>'
                                : '';
                            var suggHtml = mot.suggestion
                                ? '<div class="acmls-motivation-suggestion-box p-3 mb-3 rounded" style="background: #f0f9ff; border-left: 4px solid #0284c7;"><div class="font-weight-bold mb-1" style="color: #0369a1; font-size: 0.95rem;"><i class="fa fa-lightbulb-o mr-1"></i> Rekomendasi Langkah Belajar</div><p class="acmls-motivation-popup__suggestion m-0" style="font-size: 0.95rem; line-height: 1.6; color: #0f172a;">' + mot.suggestion + '</p></div>'
                                : '';
                            var motHtml = '<div class="acmls-motivation-popup acmls-quiz-motivation-popup is-visible" data-userid="' + userid + '" data-courseid="' + courseid + '" data-recordid="' + (mot.recordid || 0) + '">' +
                                '<div class="acmls-motivation-popup__backdrop"></div>' +
                                '<div class="acmls-motivation-popup__dialog" role="dialog" aria-modal="true">' +
                                '<div class="acmls-motivation-popup__header bg-primary text-white p-3 rounded-top">' +
                                '<div><span class="badge badge-light text-primary mb-1">' + (mot.category_label || 'Motivasi Belajar') + '</span>' +
                                '<h3 class="acmls-motivation-popup__title text-white m-0 font-weight-bold">Apresiasi & Motivasi Belajar</h3><small class="text-white-50">Disesuaikan berdasarkan evaluasi dan refleksimu</small></div></div>' +
                                '<div class="acmls-motivation-popup__body p-4">' +
                                gradeHtml +
                                '<div class="acmls-motivation-message-box p-3 mb-3 rounded" style="background: #f0fdf4; border-left: 4px solid #22c55e;"><p class="acmls-motivation-popup__message m-0 font-italic" style="font-size: 1.05rem; line-height: 1.6; color: #166534;">"' + mot.content + '"</p></div>' +
                                suggHtml +
                                '<div class="d-flex align-items-center mb-3 text-muted small"><span>Dihasilkan secara adaptif berdasarkan capaian dan refleksimu.</span></div>' +
                                '<button type="button" class="btn btn-success btn-lg w-100 acmls-quiz-motivation__dismiss">Lanjutkan Belajar</button>' +
                                '</div></div></div>';
                            $('body').append(motHtml);
                        }
                    }
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
