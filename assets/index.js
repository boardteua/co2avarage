jQuery(document).ready(function ($) {
    $(document).on('gform_confirmation_loaded', function (event, formId) {

        if (formId == co2_obj.form_ind_id || formId == co2_obj.form_biz_id) {

            $('#co2-emissions li').each(function () {

                let fieldId = $(this).find('.chart-wrp').data('id') ?? 0;
                let total = Math.round(
                    $(this).find('.chart-wrp').data('total').toString().replace(',', '')
                ) ?? 0;
                console.log(fieldId);

                let loadingMessage = $('#loading-message-' + fieldId);

                $.ajax({
                    url: '/wp-json/co2/v1/get_average',
                    method: 'POST',
                    data: JSON.stringify({
                        "form_id": formId,
                        "field_id": fieldId
                    }),
                    contentType: "application/json",
                    beforeSend: function (xhr) {
                        loadingMessage.show();
                        xhr.setRequestHeader('X-WP-Nonce', co2_obj.wpApiSettings.nonce);
                    },
                    success: function (response) {

                        let chart = $('#everage-' + fieldId);

                        Chart.register(ChartDataLabels);


                        new Chart(chart, {
                            plugins: [ChartDataLabels],
                            type: 'bar',
                            data: {
                                labels: [
                                    co2_obj.average,
                                    co2_obj.your
                                ],
                                datasets: [{
                                    data: [response.average, total],
                                    borderWidth: 0,
                                    color: 'rgba(255,255,255)',
                                    backgroundColor: ['rgba(30,122,196,1)', 'rgba(103,171,227,1)']
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                indexAxis: 'y',
                                layout: {
                                    autoPadding: false,
                                    padding: 0
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                },
                                plugins: {
                                    legend: {
                                        display: false,
                                        labels: {
                                            color: 'rgb(255, 99, 132)'
                                        }
                                    },
                                    datalabels: {
                                        anchor: 'start',
                                        align: 'end',
                                        formatter: Math.round,
                                        color: 'rgba(255,255,255,1)',
                                        font: {
                                            weight: 'normal',
                                            size: 14
                                        }
                                    }
                                }

                            }
                        });

                    },
                    error: function (xhr) {
                        console.log('Shit happens: ' + xhr.responseText);
                    },
                    complete: function () {
                        loadingMessage.hide();
                    }
                });

            })
        }
    });
});