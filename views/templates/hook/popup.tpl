{**
 * Copyright since 2024 WBShop.pl
 * @author    WBShop.pl
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

<div id="wb-gr-popup"
     class="wb-gr-popup"
     style="display:none;"
     data-ajax-url="{$wb_gr_ajax_url|escape:'htmlall':'UTF-8'}"
     data-token="{$wb_gr_token|escape:'htmlall':'UTF-8'}"
     data-trigger-type="{$wb_gr_trigger_type|escape:'htmlall':'UTF-8'}"
     data-trigger-value="{$wb_gr_trigger_value|intval}"
     data-cookie-closed-min="{$wb_gr_cookie_closed_min|intval}"
     data-cookie-subscribed-min="{$wb_gr_cookie_subscribed_min|intval}"
     data-discount-enabled="{$wb_gr_discount_enabled|intval}">

    <div class="wb-gr-popup__overlay"></div>

    <div class="wb-gr-popup__modal">
        {if $wb_gr_popup_image_desktop || $wb_gr_popup_image_mobile}
            <div class="wb-gr-popup__image">
                {if $wb_gr_popup_image_desktop && $wb_gr_popup_image_mobile}
                    <picture>
                        <source media="(max-width: 768px)" srcset="{$wb_gr_popup_image_mobile|escape:'htmlall':'UTF-8'}">
                        <img src="{$wb_gr_popup_image_desktop|escape:'htmlall':'UTF-8'}" alt="" loading="lazy">
                    </picture>
                {elseif $wb_gr_popup_image_mobile}
                    <img src="{$wb_gr_popup_image_mobile|escape:'htmlall':'UTF-8'}" alt="" loading="lazy">
                {else}
                    <img src="{$wb_gr_popup_image_desktop|escape:'htmlall':'UTF-8'}" alt="" loading="lazy">
                {/if}
            </div>
        {/if}

        <div class="wb-gr-popup__body">
            <button type="button" class="wb-gr-popup__close" aria-label="{l s='Close' d='Modules.Wbgetresponsepopup.Shop'}">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>

            <div class="wb-gr-popup__content wb-gr-popup__content--form">
                <div class="wb-gr-popup__header">
                    <h2 class="wb-gr-popup__title">{$wb_gr_text_title|escape:'htmlall':'UTF-8'}</h2>
                    <p class="wb-gr-popup__subtitle">{$wb_gr_text_subtitle|escape:'htmlall':'UTF-8'}</p>
                    {if $wb_gr_discount_enabled}
                        <div class="wb-gr-popup__badge">
                            <span>{$wb_gr_discount_badge|escape:'htmlall':'UTF-8'}</span>
                        </div>
                    {/if}
                </div>

                <form id="wb-gr-popup-form" class="wb-gr-popup__form" novalidate>
                    {if $wb_gr_field_firstname}
                        <div class="wb-gr-popup__field">
                            <input type="text"
                                   name="first_name"
                                   id="wb-gr-firstname"
                                   class="wb-gr-popup__input"
                                   autocomplete="given-name"
                                   placeholder=" ">
                            <label for="wb-gr-firstname" class="wb-gr-popup__label">
                                {l s='First name' d='Modules.Wbgetresponsepopup.Shop'} <span class="wb-gr-popup__required">*</span>
                            </label>
                        </div>
                    {/if}

                    {if $wb_gr_field_lastname}
                        <div class="wb-gr-popup__field">
                            <input type="text"
                                   name="last_name"
                                   id="wb-gr-lastname"
                                   class="wb-gr-popup__input"
                                   autocomplete="family-name"
                                   placeholder=" ">
                            <label for="wb-gr-lastname" class="wb-gr-popup__label">
                                {l s='Last name' d='Modules.Wbgetresponsepopup.Shop'} <span class="wb-gr-popup__required">*</span>
                            </label>
                        </div>
                    {/if}

                    {if $wb_gr_field_birthday}
                        <div class="wb-gr-popup__field">
                            <input type="date"
                                   name="date_of_birth"
                                   id="wb-gr-birthday"
                                   class="wb-gr-popup__input"
                                   autocomplete="bday"
                                   placeholder=" ">
                            <label for="wb-gr-birthday" class="wb-gr-popup__label">
                                {l s='Date of birth' d='Modules.Wbgetresponsepopup.Shop'}
                            </label>
                        </div>
                    {/if}

                    <div class="wb-gr-popup__field">
                        <input type="email"
                               name="email"
                               id="wb-gr-email"
                               class="wb-gr-popup__input"
                               required
                               autocomplete="email"
                               placeholder=" ">
                        <label for="wb-gr-email" class="wb-gr-popup__label">
                            {l s='Email address' d='Modules.Wbgetresponsepopup.Shop'} <span class="wb-gr-popup__required">*</span>
                        </label>
                    </div>

                    <button type="submit" class="wb-gr-popup__submit">
                        <span class="wb-gr-popup__submit-text">{$wb_gr_text_submit|escape:'htmlall':'UTF-8'}</span>
                        <span class="wb-gr-popup__submit-loading" style="display:none;">{l s='Subscribing...' d='Modules.Wbgetresponsepopup.Shop'}</span>
                    </button>

                    <div class="wb-gr-popup__field wb-gr-popup__field--consent">
                        <label class="wb-gr-popup__consent-label">
                            <input type="checkbox" name="consent" value="1" required>
                            <span class="wb-gr-popup__checkmark">
                                <svg width="10" height="8" viewBox="0 0 10 8" fill="none">
                                    <path d="M1 4L3.5 6.5L9 1" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span class="wb-gr-popup__consent-text">{$wb_gr_text_consent nofilter}</span>
                        </label>
                    </div>

                    <div class="wb-gr-popup__error" style="display:none;"></div>
                </form>
            </div>

            <div class="wb-gr-popup__content wb-gr-popup__content--success" style="display:none;">
                <div class="wb-gr-popup__success-icon">
                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                        <circle cx="24" cy="24" r="24" fill="#289b38"/>
                        <path d="M14 24L21 31L34 18" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h2 class="wb-gr-popup__title">{$wb_gr_text_success_title|escape:'htmlall':'UTF-8'}</h2>
                <p class="wb-gr-popup__subtitle">{$wb_gr_text_success_msg|escape:'htmlall':'UTF-8'}</p>

                {if $wb_gr_discount_enabled && $wb_gr_discount_show_code}
                    <div class="wb-gr-popup__discount" style="display:none;">
                        <p class="wb-gr-popup__discount-text">{$wb_gr_text_discount_text|escape:'htmlall':'UTF-8'}</p>
                        <div class="wb-gr-popup__discount-code"></div>
                        <p class="wb-gr-popup__discount-hint">{$wb_gr_text_discount_hint|escape:'htmlall':'UTF-8'}</p>
                    </div>
                {/if}
            </div>
        </div>
    </div>
</div>
