document.addEventListener('DOMContentLoaded', function() {
    var rootStyle = getComputedStyle(document.body);
    var themeColor1 = rootStyle.getPropertyValue("--theme-color-1").trim();
    var themeColor2 = rootStyle.getPropertyValue("--theme-color-2").trim();
    var themeColor3 = rootStyle.getPropertyValue("--theme-color-3").trim();
    var themeColor4 = rootStyle.getPropertyValue("--theme-color-4").trim();
    var themeColor5 = rootStyle.getPropertyValue("--theme-color-5").trim();
    var themeColor6 = rootStyle.getPropertyValue("--theme-color-6").trim();

    var themeColor1_10 = rootStyle.getPropertyValue("--theme-color-1-10").trim();
    var themeColor2_10 = rootStyle.getPropertyValue("--theme-color-2-10").trim();
    var themeColor3_10 = rootStyle.getPropertyValue("--theme-color-3-10").trim();
    var themeColor4_10 = rootStyle.getPropertyValue("--theme-color-4-10").trim();
    var themeColor5_10 = rootStyle.getPropertyValue("--theme-color-5-10").trim();
    var themeColor6_10 = rootStyle.getPropertyValue("--theme-color-6-10").trim();

    var primaryColor = rootStyle.getPropertyValue("--primary-color").trim();
    var foregroundColor = rootStyle.getPropertyValue("--foreground-color").trim();
    var separatorColor = rootStyle.getPropertyValue("--separator-color").trim();

    // ----------- Product Category
    async function fetchProductCategoryData() {
        try {
            let response = await fetch('/charts/product-category');
            let data = await response.json();
            return data;
        } catch (error) {
            console.error('Erro ao buscar dados de categoria de produtos:', error);
        }
    }

    async function productCategoryChart() {
        let productCategoryData = await fetchProductCategoryData();

        if (productCategoryData) {
            const data3 = {
                labels: productCategoryData.productCategory,
                datasets: [{
                    label: 'Número de Produtos por Categoria',
                    data: productCategoryData.number,
                    borderColor: [themeColor6, themeColor4, themeColor2, themeColor5, themeColor3, themeColor1],
                    backgroundColor: [
                        themeColor6_10,
                        themeColor4_10,
                        themeColor2_10,
                        themeColor5_10,
                        themeColor3_10,
                        themeColor1_10,
                    ],
                    borderWidth: 2
                }]
            };

            const config3 = {
                type: 'doughnut',
                data: data3,
                options: {
                    plugins: {
                        datalabels: {
                            display: false
                        }
                    },
                    maintainAspectRatio: false,
                    legend: {
                        position: "bottom",
                        labels: {
                            padding: 30,
                            usePointStyle: true,
                            fontSize: 12
                        }
                    },
                    title: {
                        display: false
                    },
                }
            };

            const cGasto = new Chart(
                document.getElementById('chartProductCategory'),
                config3
            );
        }
    }

    productCategoryChart();

    // ----------- Inventory Chart
    async function fetchInventoryData() {
        try {
            let response = await fetch('/charts/inventory', {
                headers: {
                    'Accept': 'application/json'
                }
            });
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            let data = await response.json();
            return data;
        } catch (error) {
            console.error('Erro ao buscar dados de inventário:', error);
        }
    }

    async function inventoryChart() {
        let inventoryData = await fetchInventoryData();

        if (inventoryData) {
            var rootStyle = getComputedStyle(document.body);

            var productChart = document.getElementById("inventoryChart").getContext("2d");

            var myChart = new Chart(productChart, {
                type: "bar",
                options: {
                    plugins: {
                        datalabels: {
                            display: false
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        yAxes: [{
                            gridLines: {
                                display: true,
                                lineWidth: 1,
                                color: "rgba(0,0,0,0.1)",
                                drawBorder: false
                            },
                            ticks: {
                                beginAtZero: true,
                                padding: 20
                            }
                        }],
                        xAxes: [{
                            gridLines: {
                                display: false
                            }
                        }]
                    },
                    legend: {
                        position: "bottom",
                        labels: {
                            padding: 30,
                            usePointStyle: true,
                            fontSize: 12
                        }
                    },
                    tooltips: {
                        mode: 'index',
                        intersect: false
                    }
                },
                data: {
                    labels: inventoryData.products,
                    datasets: [{
                        label: "Entrada de Estoque",
                        borderColor: themeColor6,
                        backgroundColor: themeColor6_10,
                        data: inventoryData.stock_in,
                        borderWidth: 2
                    }, {
                        label: "Saída de Estoque",
                        borderColor: themeColor2,
                        backgroundColor: themeColor2_10,
                        data: inventoryData.stock_out,
                        borderWidth: 2
                    }]
                }
            });
        }
    }

    inventoryChart();
});
