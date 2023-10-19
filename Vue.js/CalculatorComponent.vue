<template>
    <div class="overflow-hidden bg-white rounded shadow-lg min-h-[24rem] border border-stone-200 relative" v-cloak>

        <loading-component v-if="isLoading"/>

        <form v-else ref="calc" @submit.prevent="onSubmit">
            <alert-box v-if="errors">
                <div v-for="(v, k) in errors" :key="k">
                    <div v-for="error in v" :key="error">
                        {{ error }}
                    </div>
                </div>
            </alert-box>

            <div class="p-4 bg-white">
                <div class="h3 text-xl t_underline mb-4">{{ creditTitle }}</div>

                <div :class="{'flex flex-row grid grid-cols-1 lg:grid-cols-2 gap-6': horizontal}">
                    <div class="py-2">
                        <div class="flex flex-wrap items-center justify-between mb-3">
                            <div>Сумма займа:</div>
                            <div class="w-32 h-9 border-stone-200 border rounded-md text-center text-base font-light flex justify-center items-end py-1">
                                <b class="text-primary text-xl font-semibold mr-1"> {{ formatter(result.price) }}</b> <span>руб.</span>
                            </div>
                        </div>

                        <vue-slider
                            v-model="result.price"
                            :min="calc.price.min"
                            :max="calc.price.max"
                            :interval="calc.price.step"
                            :height="6"
                            :tooltip="'none'"
                            :contained="true"
                            class="secondary"/>
                    </div>

                    <div class="py-2">
                        <div class="flex flex-wrap items-center justify-between mb-3">
                            <div>Срок займа:</div>
                            <div class="w-32 h-9 border-stone-200 border rounded-md text-center text-base font-light flex justify-center items-end py-1">
                                <b class="text-primary text-xl font-semibold mr-1">{{ result.period }}</b> <span>{{  getPluralFreq(result.period) }}</span>
                            </div>
                        </div>

                        <vue-slider
                            v-model="result.period"
                            :min="calc.period.min"
                            :max="calc.period.max"
                            :interval="calc.period.step"
                            :marks="[calc.period.min, calc.period.avr, calc.period.max]"
                            :height="6"
                            :tooltip="'none'"
                            :contained="true"
                            class="primary mb-8">
                            <template v-slot:mark="{ pos, label }">
                                <div class="vue-slider-mark-custom">
                                    {{label}} {{ getPluralFreq(label) }}
                                </div>
                            </template>
                        </vue-slider>
                    </div>
                </div>
                <div v-if="showBtn">
                    <button type="submit" class="btn-secondary w-full mt-2"><span>Получить деньги</span></button>
                </div>
            </div>

            <div class="p-4 bg-back rounded-b flex" :class="{'flex-col sm:flex-row justify-between': horizontal, 'flex-col': !horizontal}">
                <div class="flex flex-wrap text-sm font-light justify-between">
                    <div class="mr-2">Вы берёте:</div>
                    <div class="font-medium"><b>{{ formatter(result.price) }}</b> руб.</div>
                </div>
                <div class="flex flex-wrap text-sm font-light justify-between">
                    <div class="mr-2">Вы вернете:</div>
                    <div class="font-medium">{{ getResultDate }}</div>
                </div>
                <div class="flex flex-wrap text-sm font-light justify-between">
                    <div class="mr-2">Сумма возврата:</div>
                    <div class="font-medium">{{ formatter(getResultTotal) }} руб.</div>
                </div>
            </div>
        </form>
    </div>
</template>

<script type="text/javascript">
import moment from 'moment'
import VueSlider from 'vue-slider-component'
import LoadingComponent from "./LoadingComponent"
import AlertBox from "./AlertBox"

const plural = require('plural-ru')

export default {
    props: {
        creditId: {default: 4, type: Number},
        showTitle: {default: false, type: Boolean},
        showBtn: {default: true, type: Boolean},
        horizontal:  {default: false, type: Boolean},
        params: {}
    },
    data() {
        return {
            isLoading: true,
            errors: null,
            moment: null,
            calc: null,
            url: null,
            result: {
                price: 0,
                period: 0,
                date: null,
                total: 0
            },
            plural: {
                "День": ["день", "дня", "дней"],
                "Неделя": ["неделя", "недели", "недель"],
                "Месяц": ["месяц", "месяца", "месяцев"],
                "Квартал": ["квартал", "квартала", "кварталов"],
                "Год": ["год", "года", "лет"]
            }
        }
    },
    created () {
        this.moment = moment
    },
    mounted() {
        this.getInit()
    },
    methods: {
        onSubmit: function (event){
            if(this.url)
                window.location = this.url + "?product_id="+this.creditId+"&sum="+this.result.price+"&term="+this.result.period;
        },

        getInit: function()
        {
            this.axios.get('/api/calc/init')
                .then(response => {
                    if(response.data.errors != undefined){
                        this.errors = response.data.errors
                    }else{
                        this.errors = null

                        if(response.data.url)
                            this.url = response.data.url

                        if(response.data.calc)
                        {
                            this.calc = response.data.calc
                            this.result.price = this.calc.price.default
                            this.result.period = this.calc.period.default

                            if(!this.calc.period.avr){
                                var avr = this.calc.period.min + ((this.calc.period.max - this.calc.period.min) / 2)
                                this.calc.period.avr = Math.round(avr)
                            }
                        }

                        this.isLoading = false
                    };
                })
                .catch(e => {
                    this.errors = e.response.data.errors
                })
                .finally(() => {

                });
        },

        getPluralFreq: function(val)
        {
            let dict = this.plural[this.calc.freq]
            return plural( val, dict[0], dict[1], dict[2])
        },

        getLastDate: function()
        {
            let $lastDate = null;
            let period = this.result.period;
            let freq = this.calc.freq;

            switch (freq) {
                case "День":
                    $lastDate = this.moment(new Date).add( period, "days");
                    break;
                case "Неделя":
                    $lastDate = this.moment(new Date).add(period, "weeks");
                    break;
                case "Месяц":
                    $lastDate = this.moment(new Date).add(period, "month");
                    break;
                case "Квартал":
                    $lastDate = this.moment(new Date).add((period * 3), "month");
                    break;
                case "Год":
                    $lastDate = this.moment(new Date).add(period, "years");
                    break;
                default: break;
            }

            return $lastDate;
        },

        getCountDays: function()
        {
            let $currentDate = this.moment(new Date);
            let $lastDate = this.getLastDate();
            return $lastDate.diff($currentDate, 'days');
        },

        getFactor: function()
        {
            let $factor = 1;
            let freq = this.calc.freq;

            switch (freq) {
                case "День":
                    break;
                case "Неделя":
                    $factor = 48;
                    break;
                case "Месяц":
                    $factor = 12;
                    break;
                case "Квартал":
                    $factor = 4;
                    break;
                case "Год":
                    break;
                default: break;
            }

            return $factor;
        },

        getTotal: function()
        {
            let $total = 0;
            let $count = this.getCountDays();
            let $factor = this.getFactor();

            if(+$count <= +this.calc.deferral_charges)
            {
                $total = parseInt(this.result.price);
            }
            else
            {
                let $sumPercent = ( this.result.price * this.calc.interest_rate * $factor / 100) * $count / this.calc.calculate_days_year;
                $total = parseInt(this.result.price) + parseInt($sumPercent);
            }

             return Math.round($total);
        },

        formatter: function(number)
        {
            return new Intl.NumberFormat('ru-RU', {
                style: 'decimal',
                currency: 'RUB',
                maximumFractionDigits: 2,
            }).format(number)
        }
    },
    computed: {

        creditTitle: function() {
            return this.calc.title && this.showTitle ? this.calc.title : ''
        },

        getResultDate: function() {
            return this.getLastDate().locale('ru').format("DD MMM YYYY")
        },

        getResultTotal: function() {
            return this.getTotal()
        }

    },
    components: {
        LoadingComponent,
        VueSlider,
        AlertBox
    }
}
</script>
