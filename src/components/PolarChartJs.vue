<script>
import { PolarArea } from 'vue-chartjs/legacy/index.js'
import {
	Chart as ChartJS,
	RadialLinearScale,
	ArcElement,
	Tooltip,
	Legend,
} from 'chart.js'

ChartJS.register(RadialLinearScale, ArcElement, Tooltip, Legend)

export default {
	name: 'PolarChartJs',
	extends: PolarArea,
	props: {
		chartData: {
			type: Object,
			required: true,
		},
		options: {
			type: Object,
			required: true,
		},
	},
	mounted() {
		this.renderChart(this.chartData, this.options)
	},
}
</script>
