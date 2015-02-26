$(document).ready(function () {

	/**
	 * Special case for Gerrit Overview
	 */
	var chart2;

	var getData = function(docType, view, callback) {
		$.ajax({
			url: "/dashboard/",
			type : 'GET',
			data : {
				type: docType,
				graph: view
			},
			dataType : 'json',
			success : callback
		});
	};

	var legend = {
		"horizontalGap": 5,
		"maxColumns": 3,
		"position": "absolute",
		"align": "center",
		"top": 20,
		"left": 0,
		"backgroundAlpha": 0.5,
		"useGraphSettings": true,
		"markerSize": 10,
		"markerBorderThickness": 10,
		"marginTop": 0
	};
	var settings = {
		marginTop: 40,
		marginLeft: 20,
		marginBottom: 20,
		marginRight: 20
	};
	var imagePath = '/_Resources/Static/Packages/WMDB.Forger/js/amcharts/images/';
	var chartCursor = {
		"cursorPosition": "mouse",
		"pan": true,
		"valueLineEnabled":true,
		"valueLineBalloonEnabled":true
	};


	/**
	 * Graph Logic
	 */
	$('.graph').each(function(index, graph) {
		var docType = $(this).data('type');
		var view = $(this).data('graph');
		var chartType = $(this).data('chart');
		var charttitle = $(this).data('charttitle');
		var divId = $(this).attr('id');
		switch(chartType) {
			case 'Velocity':
				drawVelocityChart(docType, view, divId, charttitle);
				break;
			case 'Overview':
				drawOverviewChart(docType, view, divId, charttitle);
				break;
			case 'BarHorizontal':
				drawBarHorizontalChart(docType, view, divId, charttitle);
				break;
			case 'Line':
				drawLineChart(docType, view, divId, charttitle);
				break;
			case 'Pie':
				drawPieChart(docType, view, divId, charttitle);
				break;
			default:
				$(divId).html('Chart of type '+chartType+ 'is not implemented yet');
		}
	});

	function drawVelocityChart(docType, view, divId, charttitle) {
		getData(docType, view, function(chartData) {
			var chart = AmCharts.makeChart(divId, {
				"type": "serial",
				"theme": "none",
				"autoMargins": true,
				"legend": legend,
				"marginLeft": settings.marginLeft,
				"marginBottom": settings.marginBottom,
				"marginTop": settings.marginTop,
				"marginRight": settings.marginRight,
				"pathToImages": imagePath,
				"dataProvider": chartData,
				"titles": [
					{
						"text": charttitle,
						"size": 15
					}
				],
				"valueAxes": [{
					"axisAlpha": 0,
					"dashLength": 4,
					"position": "left"
				}],
				"graphs": [{
					"id": "fromGraph",
					"lineAlpha": 1,
					"lineColor": '#f04124',
					"lineThickness": 2,
					"showBalloon": true,
					"valueField": "open",
					"title": "Sum Opened",
					"fillAlphas": 0
				}, {
					"fillAlphas": 0.2,
					"fillToGraph": "fromGraph",
					"fillColor": '#f04124',
					"lineAlpha": 1,
					"lineColor": '#43ac6a',
					"lineThickness": 2,
					"showBalloon": true,
					"valueField": "closed",
					"title": "Sum Closed"
				}],
				"chartCursor": chartCursor,
				"dataDateFormat": "YYYY-MM-DD",
				"categoryField": "date",
				"chartScrollbar": {
					"graph": "fromGraph",
					"scrollbarHeight": 30
				},
				"categoryAxis": {
					"parseDates": true,
					"dashLength": 1,
					"minorGridEnabled": true,
					"position": "top"
				}
			});
		});
	}

	function drawOverviewChart(docType, view, divId, charttitle) {
		getData(docType, view, function(chartData) {
			chart2 = AmCharts.makeChart(divId, {
				"dataProvider": chartData.chartData,
				"guides": chartData.guides,
				"type": "serial",
				"theme": "none",
				"legend": {
					"maxColumns": 3,
					"position": "absolute",
					"top": 55,
					"align": "center",
					"backgroundAlpha": 0.5,
					"useGraphSettings": true,
					"markerSize": 10,
					"markerBorderThickness": 10,
					"marginTop": 0
				},
				"marginLeft": settings.marginLeft,
				"marginBottom": settings.marginBottom,
				"marginTop": 100,
				"marginRight": settings.marginRight,
				"pathToImages": imagePath,
				"dataDateFormat": "YYYY-MM-DD",
				"titles": [
					{
						"text": charttitle,
						"size": 15
					}
				],
				"valueAxes": [{
					"id":"v1",
					"axisAlpha": 0,
					"position": "left"
				}],
				"graphs": [
					{
						"id": "g1",
						"bullet": "round",
						"bulletBorderAlpha": 1,
						"bulletColor": "#FFFFFF",
						"bulletSize": 5,
						"hideBulletsCount": 50,
						"lineThickness": 2,
						"lineColor": '#008cba',
						"title": "New",
						"useLineColorForBulletBorder": true,
						"valueField": "NEW"
					},
					{
						"id": "g2",
						"bullet": "round",
						"bulletBorderAlpha": 1,
						"bulletColor": "#FFFFFF",
						"bulletSize": 5,
						"hideBulletsCount": 50,
						"lineThickness": 2,
						"lineColor": '#43ac6a',
						"title": "Merged",
						"useLineColorForBulletBorder": true,
						"valueField": "MERGED"
					},
					{
						"id": "g3",
						"bullet": "round",
						"bulletBorderAlpha": 1,
						"bulletColor": "#FFFFFF",
						"bulletSize": 5,
						"hideBulletsCount": 50,
						"lineThickness": 1,
						"lineColor": '#f04124',
						"title": "Abandoned",
						"useLineColorForBulletBorder": true,
						"valueField": "ABANDONED"
					}],
				"chartScrollbar": {
					"graph": "g2",
					"scrollbarHeight": 30
				},
				"chartCursor": chartCursor,
				"categoryField": "date",
				"categoryAxis": {
					"parseDates": true,
					"dashLength": 1,
					"minorGridEnabled": true,
					"position": "top"
				}
			});
			chart2.addListener("rendered", zoomChart(chart2));
		});
	}

	function drawBarHorizontalChart(docType, view, divId, charttitle) {
		getData(docType, view, function(chartData) {
			var graphs = [];
			$.each(chartData.lookup, function(index, data) {
				var singleBar = {
					"balloonText": "<b>[[title]]</b><br><span style='font-size:14px'>[[category]]: <b>[[value]]</b></span>",
					"fillAlphas": 0.8,
					"labelText": "[[value]]",
					"lineAlpha": 0.3,
					"title": data.title,
					"type": "column",
					"lineColor": data.color,
					"valueField": data.lookup
				};
				graphs.push(singleBar);
			});
			var chart = AmCharts.makeChart(divId, {
				"type": "serial",
				"theme": "none",
				"autoMargins": true,
				"marginLeft": 20,
				"marginBottom": 40,
				"rotate": true,
				"pathToImages": imagePath,
				"dataProvider": chartData.bars,
				"titles": [
					{
						"text": charttitle,
						"size": 15
					}
				],
				"valueAxes": [{
					"stackType": "regular",
					"gridColor":"#000000",
					"gridAlpha": 0.15,
					"dashLength": 1,
					"autoGridCount": false,
					"labelFrequency": 3,
					"labelRotation": 45,
					"gridCount": 25
				}],
				"graphs": graphs,
				"categoryField": "panel",
				"categoryAxis": {
					"gridPosition": "start",
					"gridAlpha": 0.15,
					"tickPosition":"start",
					"tickLength":20,
					"dashLength": 1
				}
			});
		});
	}

	function drawLineChart(docType, view, divId, charttitle) {
		getData(docType, view, function(chartData) {
			var graphs = [];
			$.each(chartData.lines, function(index, data) {
				var singleLine = {
					"lineAlpha": 1,
					"lineColor": data.color,
					"lineThickness": 2,
					"showBalloon": true,
					"valueField": index,
					"fillAlphas": 0.5,
					"title": data.title,
					"bullet": "round",
					"bulletSize": 6
				};
				graphs.push(singleLine);
			});

			var chart = AmCharts.makeChart(divId, {
				"type": "serial",
				"theme": "none",
				"autoMargins": true,
				"legend": legend,
				"marginLeft": settings.marginLeft,
				"marginBottom": settings.marginBottom,
				"marginTop": settings.marginTop,
				"marginRight": settings.marginRight,
				"pathToImages": imagePath,
				"dataProvider": chartData.chartData,
				"guides": chartData.guides,
				"titles": [
					{
						"text": charttitle,
						"size": 15
					}
				],
				"valueAxes": [{
					"axisAlpha": 1,
					"dashLength": 4,
					"position": "left"
				}],
				"graphs": graphs,
				"chartCursor": chartCursor,
				"dataDateFormat": "YYYY-MM-DD",
				"categoryField": "date",
				"categoryAxis": {
					"parseDates": true,
					"axisAlpha": 0,
					"minHorizontalGap":25,
					"gridAlpha": 0,
					"tickLength": 0,
					"twoLineMode":true,
					"dateFormats":[{
						period: 'fff',
						format: 'JJ:NN:SS'
					}, {
						period: 'ss',
						format: 'JJ:NN:SS'
					}, {
						period: 'mm',
						format: 'JJ:NN'
					}, {
						period: 'hh',
						format: 'JJ:NN'
					}, {
						period: 'DD',
						format: 'DD'
					}, {
						period: 'WW',
						format: 'DD'
					}, {
						period: 'MM',
						format: 'MMM'
					}, {
						period: 'YYYY',
						format: 'YYYY'
					}]
				}
			});
			chart.addListener("clickGraphItem", function(clickedItem) {
				if(clickedItem.target.bulletColorR == '#ff0000') {
					//new tickets
					window.open("https://forge.typo3.org/projects/typo3cms-core/issues?set_filter=1&f[]=status_id&op[status_id]=*&f[]=created_on&op[created_on]==&v[created_on][]="+clickedItem.item.dataContext.date, "_blank");
				} else if(clickedItem.target.bulletColorR == '#43ac6a') {
					//closed tickets
					window.open("https://forge.typo3.org/projects/typo3cms-core/issues?set_filter=1&f[]=status_id&op[status_id]=c&f[]=updated_on&op[updated_on]==&v[updated_on][]="+clickedItem.item.dataContext.date, "_blank")
				}
			});
		});
	}

	function drawPieChart(docType, view, divId, charttitle) {
		getData(docType, view, function(chartData) {
			var chart = AmCharts.makeChart(divId, {
				"type": "pie",
				"theme": "none",
				"marginLeft": 0,
				"marginBottom": 0,
				"marginTop": 0,
				"marginRight": 0,
				"dataProvider": chartData.chartData,
				"titles": [
					{
						"text": charttitle,
						"size": 15
					}
				],
				"titleField": "name",
				"valueField": "value",
				"labelRadius": -40,
				//"radius": "42%",
				//"innerRadius": "60%",
				"pullOutRadius": 0,
				"colors": [
					'#43ac6a',
					'#f04124'
				],
				"labelText": "[[title]] [[percents]]% "
			});
		});
	}

	/**
	 * UTILITY
	 * @param chart2
	 */
	function zoomChart(chart2){
		chart2.zoomToIndexes(chart2.dataProvider.length - 30, chart2.dataProvider.length - 1);
	}

	$('.expandable').each(function(index, expandContainer) {
		$(expandContainer).attr('data-origheight', $(expandContainer).height());
		if($(expandContainer).height() > 120) {
			$(expandContainer).height(120);
			$(expandContainer).next('.moreBtn').bind('click', function() {
				$(this).toggleClass('success');
				$(this).toggleClass('alert');
				var clicks = $(this).data('clicks');
				if (clicks) {
					$(this).html('...more');
					$(expandContainer).animate({height: 120}, 200);
				} else {
					$(this).html('...less');
					$(expandContainer).animate({height: $(expandContainer).attr('data-origheight')}, 200);
				}
				$(this).data("clicks", !clicks);
			});
			$(expandContainer).next('.moreBtn').css('display', 'inline');
		}
	});

});