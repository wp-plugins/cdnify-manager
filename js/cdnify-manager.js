jQuery(document).ready(function() {
    var analytics_input      = jQuery("#cdnify-dashboard-graph").data("input");
    var analytics_input_last = jQuery("#cdnify-dashboard-graph").data("input-last");
    var style_colour         = jQuery("#adminmenu li.wp-has-current-submenu a.wp-has-current-submenu").css("background-color");

    if(jQuery("#cdnify-dashboard-graph").length) {
        jQuery("#cdnify-dashboard-graph").highcharts({
            chart: {
                type: 'area'
            },
            title: {
                text: ''
            },
            subtitle: {
                text: ''
            },
            xAxis: {
                labels: {
                    formatter: function() {
                        return this.value;
                    }
                }
            },
            yAxis: {
                title: {
                    text: ''
                },
                labels: {
                    formatter: function() {
                        return this.value;
                    }
                }
            },
            tooltip: {
                headerFormat: '{point.x}th<br/>',
                pointFormat: '<span style="color: ' + style_colour + '">{series.name}</span> : <b>{point.y:,.0f}</b><br/>',
                shared: true,
                crosshairs: true,
                borderRadius: 0
            },
            plotOptions: {
                area: {
                    pointStart: 1,
                    marker: {
                        enabled: false,
                        symbol: 'circle',
                        radius: 2,
                        states: {
                            hover: {
                                enabled: true
                            }
                        }
                    }
                }
            },
            series: [{
                name: 'This Month',
                data: analytics_input,
                color: style_colour,
                zIndex: 1,
    	    	marker: {
    	    		fillColor: 'white',
    	    		lineWidth: 2,
    	    		lineColor: style_colour
    	    	}
            }, {
                name: 'Last Month',
                data: analytics_input_last,
                color: "#f0f0f0",
                zIndex: 1,
    	    	marker: {
    	    		fillColor: 'white',
    	    		lineWidth: 2,
    	    		lineColor: "#f0f0f0"
    	    	}
            }],
            credits: {
                enabled: false
            }
        });
    }
});