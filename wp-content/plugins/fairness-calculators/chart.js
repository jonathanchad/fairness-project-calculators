window.onload = function () {
  var ctx = document.getElementById("myChart");
  if (!ctx) {return}
  const chartObj = window.chartObj;
  const labels = Object.keys(chartObj);
  const salaryData = labels.map((key) => (chartObj[key].yearly_total));
  const oldSalaryData = labels.map((key) => (chartObj[key].old_total));

  const dataset = [...salaryData, ...oldSalaryData].filter((n)=>(n));
  const minStep = Math.round(Math.min(...dataset)/1000) * 1000 - 1000;

  var myChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Your salary',
          backgroundColor: "#52a7c6",
          borderColor: "#52a7c6",
          fill: false,
          data: salaryData,
          borderWidth: 3
        }, {
          label: 'Your old salary',
          backgroundColor: "#69696b",
          borderColor: "#69696b",
          fill: false,
          data: oldSalaryData,
          borderWidth: 3
        }
      ]
    },
    options: {
      layout: {
        padding: {
          top: 10,
          bottom: 10
        }
      },
      scales: {
        xAxes: [
          {
            display: true,
            scaleLabel: {
              display: false,
              labelString: 'Year'
            }
          }
        ],
        yAxes: [
          {
            display: true,
            scaleLabel: {
              display: true,
              labelString: 'Yearly Salary'
            },
            ticks: {
              beginAtZero: false,
              min: minStep,
            }
          }
        ]
      },
      maintainAspectRatio: false,
      responsive: true,
    }
  });
}
