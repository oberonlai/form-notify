import {__} from '@wordpress/i18n';
import {useBlockProps, InnerBlocks} from '@wordpress/block-editor';

export default function Save({attributes}) {
    const blockProps = useBlockProps.save({
        className: 'form-notify-line-login'
    });
    return (
        <div {...blockProps}>
            <InnerBlocks.Content/>
        </div>
    );
}