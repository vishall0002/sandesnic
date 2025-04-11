import Dygraph from 'dygraphs';
import 'dygraphs/src/extras/smooth-plotter.js';

import {
    blue,
    green
} from 'ansi-colors';

var gdPathCF, gdPathDW, gdPathLU, gdPathDWLU, gdPathAU, gdPathDRTM;

$(document).ready(function () {
    var eleDBD = $("#btnRefresh");
    var path = eleDBD.data('api-path');
    gdPathCF = eleDBD.data('gd-path');
    gdPathDW = eleDBD.data('gd-dwpath');
    gdPathLU = eleDBD.data('gd-lupath');
    gdPathDWLU = eleDBD.data('gd-dwlupath');
    gdPathDRTM = eleDBD.data('gd-drtmpath');
    gdPathAU = eleDBD.data('gd-aupath');
    pullDBDChats(path);
    pullGraphData();
});

$("#btnRefresh").click(function () {
    var path = this.dataset.apiPath;
    pullDBDChats(path);
    pullGraphData();
})

function pullDBDChats(apiPath) {
    $.ajax({
        url: apiPath,
        beforeSend: function () {
            $('.progress').addClass('d-block');
            $('.dbdvals').addClass('blurry-text');
        },
        success: function (dbdata) {
            if (!dbdata.OLUCount){
                $(".online-users").removeClass('h1').removeClass('dashboard-value').addClass('blink_text').text('Connecting...');
            } else {
                $(".online-users").text(dbdata.OLUCount);
            }
            $(".db_o_count").text(dbdata.OCount);
            $(".db_ou_count").text(dbdata.OUCount);
            $(".db_e_count").text(dbdata.ECount);
            $(".db_re_count").text(dbdata.ERCount);
            $(".db_m_count").text(dbdata.MCount);
            $(".db_min_count").text(dbdata.MinCount);
            $(".last-updated-at").text('Last updated at ' + dbdata.LAU);
        },
        complete: function () {
            $('.dbdvals').removeClass('blurry-text');
            $('.progress').removeClass('d-block').addClass('d-none');

        }
    });
}

function pullDBDGroups(apiPath) {
    $.ajax({
        url: apiPath,
        beforeSend: function () {
            $('.dbdvals').addClass('blurry-text');
        },
        success: function (dbdata) {
            gcsr = dbdata.gcsr;
            $("#db_groups").text(gcsr.group_count);
            $("#db_groups_messages").text(gcsr.message_count);
            $("#db_groups_files_shared").text(gcsr.file_count);
        },
        complete: function () {
            $('.dbdvals').removeClass('blurry-text');
        }
    });
}

function pullGraphData() {

    var dvCF = document.getElementById("dvChatFlow");
    var dvCFL = document.getElementById("dvChatFlowL");
    $.get(gdPathCF, function (data) {
        const gdcf = new Dygraph(dvCF, data, {
            // plotter: smoothPlotter,
            plotter: barChartPlotter,
            drawGrid: true,
            fillGraph: true,
            fillAlpha: 0.3,
            colors: ["rgb(101,61,144)",
                "rgb(255,100,100)",
                "#00DD55",
                "rgba(50,50,200,0.4)"
            ],
            showRangeSelector: true,
            rangeSelectorHeight: 30,
            rangeSelectorPlotStrokeColor: '#d0c4dd',
            labelsDiv: dvCFL,
            legend: 'always',
            legendFormatter: legendFormatter,
            rangeSelectorPlotFillColor: '#d0c4dd'
        });
    });

    var dvDW = document.getElementById("dvDWChatFlow");
    var dvDWL = document.getElementById("dvDWChatFlowL");
    $.get(gdPathDW, function (data) {
        const gddw = new Dygraph(dvDW, data, {
            plotter: barChartPlotter,
            drawGrid: true,
            fillGraph: true,
            fillAlpha: 0.3,
            colors: ["#13acde",
                "rgb(255,100,100)",
                "#00DD55",
                "rgba(50,50,200,0.4)"
            ],
            showRangeSelector: true,
            rangeSelectorHeight: 30,
            rangeSelectorPlotStrokeColor: '#89d5ee',
            labelsDiv: dvDWL,
            legend: 'always',
            legendFormatter: legendFormatter,
            rangeSelectorPlotFillColor: '#89d5ee'
        });
    });

    var dvLU = document.getElementById("dvLiveUser");
    var dvLUL = document.getElementById("dvLiveUserL");
    if (dvLU) {
        $.get(gdPathLU, function (data) {
            const gdlu = new Dygraph(dvLU, data, {
                // plotter: smoothPlotter,
                drawGrid: true,
                fillGraph: true,
                fillAlpha: 0.3,
                colors: ["rgb(29,148,91)",
                    "rgb(255,100,100)",
                    "#00DD55",
                    "rgba(50,50,200,0.4)"
                ],
                showRangeSelector: true,
                rangeSelectorHeight: 30,
                rangeSelectorPlotStrokeColor: '#badfcd',
                labelsDiv: dvLUL,
                legend: 'always',
                legendFormatter: legendFormatter,
                rangeSelectorPlotFillColor: '#badfcd'
            });
        });
    }
    var dvDWLU = document.getElementById("dvDWLiveUser");
    var dvDWLUL = document.getElementById("dvDWLiveUserL");
    if (dvDWLU) {
        $.get(gdPathDWLU, function (data) {
            const gddwlu = new Dygraph(dvDWLU, data, {
                plotter: barChartPlotter,
                drawGrid: true,
                fillGraph: true,
                fillAlpha: 0.3,
                colors: ["#f37123",
                    "rgb(255,100,100)",
                    "#00DD55",
                    "rgba(50,50,200,0.4)"
                ],
                showRangeSelector: true,
                rangeSelectorHeight: 30,
                rangeSelectorPlotStrokeColor: '#f37123',
                labelsDiv: dvDWLUL,
                legend: 'always',
                legendFormatter: legendFormatter,
                rangeSelectorPlotFillColor: '#f37123'
            });
        });
    }
    var dvDRTM = document.getElementById("dvDRTM");
    var dvDRTML = document.getElementById("dvDRTML");
    if (dvDRTM) {
        $.get(gdPathDRTM, function (data) {
            const gddwlu = new Dygraph(dvDRTM, data, {
                plotter: barChartPlotter,
                drawGrid: true,
                fillGraph: true,
                fillAlpha: 0.3,
                colors: ["#f37123",
                    "rgb(255,100,100)",
                    "#00DD55",
                    "rgba(50,50,200,0.4)"
                ],
                showRangeSelector: true,
                rangeSelectorHeight: 30,
                rangeSelectorPlotStrokeColor: '#f37123',
                labelsDiv: dvDRTML,
                legend: 'always',
                legendFormatter: legendFormatter,
                rangeSelectorPlotFillColor: '#f37123'
            });
        });
    }
    var dvAU = document.getElementById("dvActiveUser");
    var dvAUL = document.getElementById('dvActiveUserL');
    if (dvAU) {
        $.get(gdPathAU, function (data) {
            const gdau = new Dygraph(dvAU, data, {
                plotter: barChartPlotter,
                drawGrid: true,
                fillGraph: true,
                fillAlpha: 0.3,
                colors: ["#18bc9c",
                    "rgb(255,100,100)",
                    "#00DD55",
                    "rgba(50,50,200,0.4)"
                ],
                showRangeSelector: true,
                rangeSelectorHeight: 30,
                rangeSelectorPlotStrokeColor: '#18bc9c',
                labelsDiv: dvAUL,
                legend: 'always',
                legendFormatter: legendFormatter,
                rangeSelectorPlotFillColor: '#18bc9c'
            });
        });
    }

}

function barChartPlotter(e) {
    var ctx = e.drawingContext;
    var points = e.points;
    var y_bottom = e.dygraph.toDomYCoord(0);

    ctx.fillStyle = darkenColor(e.color);

    var min_sep = Infinity;
    for (var i = 1; i < points.length; i++) {
        var sep = points[i].canvasx - points[i - 1].canvasx;
        if (sep < min_sep) min_sep = sep;
    }
    var bar_width = Math.floor(2.0 / 3 * min_sep);

    // Do the actual plotting.
    for (var i = 0; i < points.length; i++) {
        var p = points[i];
        var center_x = p.canvasx;

        ctx.fillRect(center_x - bar_width / 2, p.canvasy,
            bar_width, y_bottom - p.canvasy);

        ctx.strokeRect(center_x - bar_width / 2, p.canvasy,
            bar_width, y_bottom - p.canvasy);
    }
}

function darkenColor(colorStr) {
    // Defined in dygraph-utils.js
    var color = Dygraph.toRGB_(colorStr);
    color.r = Math.floor((255 + color.r) / 2);
    color.g = Math.floor((255 + color.g) / 2);
    color.b = Math.floor((255 + color.b) / 2);
    return 'rgb(' + color.r + ',' + color.g + ',' + color.b + ')';
}

function legendFormatter(data) {
    if (data.x == null) {
        // This happens when there's no selection and {legend: 'always'} is set.
        return '<br>' + data.series.map(function (series) {
            return series.dashHTML + ' ' + series.labelHTML
        }).join('<br>');
    }

    var html = this.getLabels()[0] + ': ' + data.xHTML;
    data.series.forEach(function (series) {
        if (!series.isVisible) return;
        var labeledData = series.labelHTML + ': ' + series.yHTML;
        if (series.isHighlighted) {
            labeledData = '<b>' + labeledData + '</b>';
        }
        html += '<br>' + series.dashHTML + ' ' + labeledData;
    });
    return html;
}

$('body').on('click', '.btn-pivot', function (e) {
    var apiPath = this.dataset.pullPath;
    var objid = this.dataset.objid;
    pivotEmployeeData(apiPath, objid);
    e.preventDefault();
});


function pivotEmployeeData(apiPath, objid) {
    var dateRange = $(".input-daterange").val();
    var Papa = require('papaparse');
    var PivotT = require('pivottable');
    require('jquery-ui');
    require('jquery-ui/ui/widgets/sortable');
    require('jquery-ui/ui/disable-selection');

    var sum = $.pivotUtilities.aggregatorTemplates.sum;
    var numberFormat = $.pivotUtilities.numberFormat;
    var intFormat = numberFormat({
        digitsAfterDecimal: 0
    });
    var heatmap =  $.pivotUtilities.renderers["Heatmap"];
    var renderers = $.extend(
        $.pivotUtilities.renderers,
        $.pivotUtilities.plotly_renderers,
        $.pivotUtilities.d3_renderers,
        $.pivotUtilities.export_renderers
    );
    $.ajax({
        method: "POST",
        url: apiPath,
        data: {
            'objid': objid, 'dateRange' : dateRange
        },
        beforeSend: function () {
            $('.progress-barm').addClass('d-block').addClass('progress-bar-animated');
        },
        success: function (dbdata) {
            Papa.parse(dbdata, {
                skipEmptyLines: true,
                error: function (e) {
                    alert(e)
                },
                complete: function (parsed) {
                    console.log(parsed);
                    $("#output").pivot(parsed.data, {
                        rows: ["Employee"],
                        cols: ["Day"],
                        aggregator: sum(intFormat)(["Count"]),
                        renderer: heatmap,
                        rowOrder: 'value_z_to_a'
                    });
                }
            });
        },
        complete: function () {
            $('.progress-barm').removeClass('d-block').addClass('d-none').removeClass('progress-bar-animated');
        }
    });
}