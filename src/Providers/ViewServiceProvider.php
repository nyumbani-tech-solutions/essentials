<?php

namespace Nyumbani\Essentials\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register the Blade directives.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function boot(): void
    {
        $this->handleDirectives();
    }

    /**
     * Register the Blade directives that wrap the formatting helpers.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function handleDirectives(): void
    {
        //  Format strings with underscores into perfect strings
        Blade::directive('prettify', fn ($expression) => "<?php echo prettify($expression); ?>");

        //  Format numbers
        Blade::directive('numeric', fn ($expression) => "<?php echo numeric($expression); ?>");

        //  Format amounts to money
        Blade::directive('money', fn ($expression) => "<?php echo money($expression); ?>");

        //  Spell out amounts in words for receipts, escaped as bad input comes back as it is
        Blade::directive('moneyinwords', fn ($expression) => "<?php echo e(money_in_words($expression)); ?>");

        //  Spell out whole numbers in words, escaped as bad input comes back as it is
        Blade::directive('numberinwords', fn ($expression) => "<?php echo e(number_in_words($expression)); ?>");

        //  Print the currency code
        Blade::directive('currency', fn () => '<?php echo currency(); ?>');

        //  Format percentage
        Blade::directive('percentage', function ($expression) {
            //  Split the expression into the value and the total
            [$value, $total] = explode(',', preg_replace("/[\(\)\\\"\']/", '', $expression));

            //  Print the percentage
            return "<?php echo percentage(trim($value), trim($total)); ?>";
        });

        //  Format date
        Blade::directive('date', fn ($expression) => "<?php echo format_date($expression); ?>");

        //  Format time
        Blade::directive('time', fn ($expression) => "<?php echo format_time($expression); ?>");

        //  Format date time
        Blade::directive('datetime', fn ($expression) => "<?php echo format_date_time($expression); ?>");

        //  Format date time for humans
        Blade::directive('dateforhumans', fn ($expression) => "<?php echo format_humans($expression); ?>");

        //  Print the input Laravel reads to choose the error bag, without a global helper that another package could shadow
        Blade::directive('bag', fn ($expression) => '<input type="hidden" name="_error_bag" value="<?php echo e('.$expression.'); ?>">');
    }
}
