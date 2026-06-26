export function clearFieldValidation(root = document) {
    root.querySelectorAll('.is-invalid').forEach((input) => input.classList.remove('is-invalid'));
    root.querySelectorAll('[data-validation-error]').forEach((node) => {
        node.textContent = '';
    });
}

export function showFieldValidation(errors = [], root = document) {
    clearFieldValidation(root);

    errors.forEach((error) => {
        const feedback = root.querySelector(`[data-validation-error="${error.field}"]`);
        const input = feedback?.previousElementSibling;

        if (feedback) {
            feedback.textContent = error.message;
        }

        if (input) {
            input.classList.add('is-invalid');
        }
    });
}

export function requiredString(value) {
    return typeof value === 'string' && value.trim().length > 0;
}

export function positiveNumber(value) {
    return Number.isFinite(Number(value)) && Number(value) > 0;
}
