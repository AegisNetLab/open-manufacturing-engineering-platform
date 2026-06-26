export function byId(id) {
    return document.getElementById(id);
}

export function optionalById(id) {
    return document.getElementById(id) || null;
}

export function text(node, value) {
    if (node) {
        node.textContent = value;
    }
}

export function show(node) {
    node?.classList.remove('d-none');
}

export function hide(node) {
    node?.classList.add('d-none');
}
