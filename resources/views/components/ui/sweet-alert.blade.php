{{-- File: resources/views/components/sweet-alert.blade.php --}}
<style>
    .animate-scale-in {
        animation: scale-in 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }

    .animate-fade-in {
        animation: fade-in 0.25s ease forwards;
    }

    .animate-bounce-icon {
        animation: bounce-icon 0.6s ease forwards;
    }

    .animate-ripple {
        animation: ripple 0.6s ease-out forwards;
    }

    .stroke-dash {
        stroke-dasharray: 50;
        stroke-dashoffset: 50;
        animation: checkmark 0.5s ease 0.25s forwards;
    }

    .cross-dash {
        stroke-dasharray: 30;
        stroke-dashoffset: 30;
        animation: checkmark 0.4s ease 0.2s forwards;
    }

    @keyframes scale-in {
        0% {
            opacity: 0;
            transform: scale(0.85) translateY(12px);
        }

        100% {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    @keyframes fade-in {
        0% {
            opacity: 0;
        }

        100% {
            opacity: 1;
        }
    }

    @keyframes bounce-icon {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.15);
        }
    }

    @keyframes ripple {
        0% {
            transform: scale(0);
            opacity: 0.6;
        }

        100% {
            transform: scale(2.5);
            opacity: 0;
        }
    }

    @keyframes checkmark {
        0% {
            stroke-dashoffset: 50;
        }

        100% {
            stroke-dashoffset: 0;
        }
    }
</style>

<div x-data="sweetAlertGlobal()" @global-alert.window="fire($event.detail)">

    <div x-cloak x-show="alert.show"
        x-transition:enter="animate-fade-in" x-transition:leave="transition-opacity duration-200" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4" style="background: rgba(0,0,0,0.45);">

        <div x-show="alert.show" @click.away="closeOnBackdrop()"
            x-transition:enter="animate-scale-in" x-transition:leave="transition-all duration-200" x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-sm shadow-2xl" :style="cardStyle()">

            <div class="relative z-10 px-8 pt-10 pb-6 text-center">

                <div class="flex justify-center mb-6">
                    <div class="relative">
                        <div class="absolute inset-0 animate-ripple" :style="rippleColor()"></div>
                        <div class="relative w-20 h-20 flex items-center justify-center animate-bounce-icon" :style="iconBg()">

                            <template x-if="alert.type === 'success'">
                                <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
                                    <path class="stroke-dash" d="M8 18 L15 25 L28 11" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </template>

                            <template x-if="alert.type === 'error'">
                                <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
                                    <path class="cross-dash" d="M11 11 L25 25" stroke="white" stroke-width="3" stroke-linecap="round" />
                                    <path class="cross-dash" d="M25 11 L11 25" stroke="white" stroke-width="3" stroke-linecap="round" style="animation-delay: 0.1s" />
                                </svg>
                            </template>

                            <template x-if="alert.type === 'info'">
                                <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                            </template>

                            <template x-if="alert.type === 'question'">
                                <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                </svg>
                            </template>

                        </div>
                    </div>
                </div>

                <h2 x-show="alert.title" x-text="alert.title" class="text-xl font-black mb-2 text-gray-800"></h2>
                <p x-show="alert.message" x-text="alert.message" class="text-sm text-gray-500 mb-2 leading-relaxed"></p>
            </div>

            <div class="relative z-10 px-8 pb-8 flex gap-3" :class="alert.cancelText ? 'flex-row' : 'flex-col'">

                <button x-show="alert.cancelText" @click="cancel()"
                    class="flex-1 py-3 px-5 text-sm font-bold transition-all duration-200 active:scale-95 border border-gray-200 text-gray-500 hover:bg-gray-50"
                    style="border-radius:999px">
                    <span x-text="alert.cancelText"></span>
                </button>

                <button x-show="alert.confirmText" @click="confirm()"
                    class="flex-1 py-3 px-5 text-sm font-bold transition-all duration-200 active:scale-95 text-white" :style="confirmStyle()">
                    <span x-text="alert.confirmText"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function sweetAlertGlobal() {
        return {
            alert: {
                show: false,
                type: 'info',
                title: '',
                message: '',
                confirmText: 'OK',
                cancelText: '',
                onConfirm: null,
                onCancel: null,
                closeOnBackdrop: true
            },
            _palettes: {
                success: {
                    orb1: '#6ee7b7',
                    orb2: '#a7f3d0',
                    iconFrom: '#34d399',
                    iconTo: '#059669',
                    ripple: '#6ee7b7',
                    btn: 'linear-gradient(135deg,#34d399,#059669)',
                    btnShadow: 'rgba(5,150,105,0.28)'
                },
                error: {
                    orb1: '#fca5a5',
                    orb2: '#fda4af',
                    iconFrom: '#f87171',
                    iconTo: '#dc2626',
                    ripple: '#fca5a5',
                    btn: 'linear-gradient(135deg,#f87171,#dc2626)',
                    btnShadow: 'rgba(220,38,38,0.28)'
                },
                info: {
                    orb1: '#93c5fd',
                    orb2: '#bfdbfe',
                    iconFrom: '#60a5fa',
                    iconTo: '#2563eb',
                    ripple: '#93c5fd',
                    btn: 'linear-gradient(135deg,#60a5fa,#2563eb)',
                    btnShadow: 'rgba(37,99,235,0.28)'
                },
                question: {
                    orb1: '#c4b5fd',
                    orb2: '#ddd6fe',
                    iconFrom: '#a78bfa',
                    iconTo: '#7c3aed',
                    ripple: '#c4b5fd',
                    btn: 'linear-gradient(135deg,#a78bfa,#7c3aed)',
                    btnShadow: 'rgba(124,58,237,0.28)'
                },
            },
            p() {
                return this._palettes[this.alert.type] || this._palettes.info;
            },
            cardStyle() {
                const c = this.p();
                return `border-radius: 1rem; box-shadow: 0 24px 64px rgba(0,0,0,0.13), 0 0 0 1px rgba(0,0,0,0.05); background: radial-gradient(ellipse 75% 65% at 110% -10%, ${c.orb1}cc 0%, ${c.orb2}55 45%, transparent 70%), radial-gradient(ellipse 50% 45% at -10% 110%, ${c.orb2}66 0%, transparent 65%), #ffffff;`;
            },
            iconBg() {
                const c = this.p();
                return `border-radius:9999px; background: linear-gradient(135deg, ${c.iconFrom}, ${c.iconTo}); box-shadow: 0 8px 24px ${c.btnShadow}`;
            },
            rippleColor() {
                return `border-radius:9999px; background: ${this.p().ripple}; opacity: 0.25`;
            },
            confirmStyle() {
                const c = this.p();
                return `border-radius:999px; background: ${c.btn}; box-shadow: 0 8px 20px ${c.btnShadow}`;
            },

            fire(opts) {
                this.alert = {
                    show: false,
                    type: opts.type || 'info',
                    title: opts.title || '',
                    message: opts.message || '',
                    confirmText: opts.confirmText || 'OK',
                    cancelText: opts.cancelText || '',
                    onConfirm: opts.onConfirm || null,
                    onCancel: opts.onCancel || null,
                    closeOnBackdrop: opts.closeOnBackdrop !== false
                };
                this.$nextTick(() => {
                    this.alert.show = true;
                });
            },

            confirm() {
                const cb = this.alert.onConfirm;
                this.alert.show = false;
                if (cb) setTimeout(cb, 250);
            },

            cancel() {
                const cb = this.alert.onCancel;
                this.alert.show = false;
                if (cb) setTimeout(cb, 250);
            },

            closeOnBackdrop() {
                if (this.alert.closeOnBackdrop && !this.alert.cancelText) {
                    this.alert.show = false;
                }
            }
        };
    }

    // FUNGSI HELPER GLOBAL (Bisa dipanggil dari mana saja)
    window.ShowAlert = function(options) {
        window.dispatchEvent(new CustomEvent('global-alert', {
            detail: options
        }));
    };

    window.ShowConfirm = function(options, onConfirmCallback) {
        window.dispatchEvent(new CustomEvent('global-alert', {
            detail: {
                type: 'question',
                cancelText: 'Batal', // Default ada tombol batal
                ...options,
                onConfirm: onConfirmCallback
            }
        }));
    };
</script>