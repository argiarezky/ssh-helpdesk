

import Alpine from 'alpinejs';

Alpine.data('datePicker', initialValue => {
    const parseDate = (value) => {
        if (! value) {
            return new Date();
        }

        const [year, month, day] = value.split('-').map(Number);

        return new Date(year, month - 1, day);
    };

    const formatDateKey = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
    };

    const selectedDate = parseDate(initialValue);

    return {
        value: initialValue || '',
        viewDate: new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1),
        open: false,
        weekdays: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],

        get monthLabel() {
            return new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(this.viewDate);
        },

        get displayValue() {
            if (! this.value) {
                return 'Pilih tanggal';
            }

            return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(parseDate(this.value));
        },

        get calendarDays() {
            const year = this.viewDate.getFullYear();
            const month = this.viewDate.getMonth();
            const firstWeekday = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const days = Array(firstWeekday).fill(null);

            for (let day = 1; day <= daysInMonth; day += 1) {
                days.push(new Date(year, month, day));
            }

            while (days.length < 42) {
                days.push(null);
            }

            return days;
        },

        toggle() {
            this.open = ! this.open;

            if (this.open && this.value) {
                const selected = parseDate(this.value);
                this.viewDate = new Date(selected.getFullYear(), selected.getMonth(), 1);
            }
        },

        moveMonth(amount) {
            this.viewDate = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth() + amount, 1);
        },

        isSelected(day) {
            return day !== null && formatDateKey(day) === this.value;
        },

        isToday(day) {
            return day !== null && formatDateKey(day) === formatDateKey(new Date());
        },

        selectDay(day) {
            if (day === null) {
                return;
            }

            this.value = formatDateKey(day);
            this.open = false;
        },

        selectToday() {
            this.value = formatDateKey(new Date());
            this.viewDate = parseDate(this.value);
            this.open = false;
        },

        clear() {
            this.value = '';
            this.open = false;
        },
    };
});

window.Alpine = Alpine;

Alpine.start();
