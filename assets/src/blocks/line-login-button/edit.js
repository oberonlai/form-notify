import {__} from '@wordpress/i18n'
import {useBlockProps, InnerBlocks} from '@wordpress/block-editor'
import {useSelect} from '@wordpress/data'

export default function Edit({attributes, setAttributes}) {
    const blockProps = useBlockProps({
        className: "form-notify-line-login"
    });

    const permalink = useSelect(
        (select) => select('core/editor').getPermalink(),
        []
    );

    const MY_TEMPLATE = [
        ['core/button', {
            text: `<span><img src="${lineLoginButtonParams.buttonIconUrl}">LINE Login</span>`,
            url: permalink + '?lgmode=true'
        }]
    ];

    return (
        <div {...blockProps}>
            <InnerBlocks template={MY_TEMPLATE} templateLock="all"/>
        </div>
    );
}