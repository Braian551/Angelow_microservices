import 'package:flutter/material.dart';

class SearchablePickerField<T> extends StatelessWidget {
  const SearchablePickerField({
    super.key,
    required this.label,
    required this.searchHint,
    required this.options,
    required this.itemLabel,
    required this.onChanged,
    this.value,
    this.prefixIcon,
    this.validator,
    this.leadingBuilder,
    this.enabled = true,
  });

  final String label;
  final String searchHint;
  final List<T> options;
  final String Function(T item) itemLabel;
  final ValueChanged<T?> onChanged;
  final T? value;
  final IconData? prefixIcon;
  final String? Function(T?)? validator;
  final Widget Function(BuildContext context, T item)? leadingBuilder;
  final bool enabled;

  Future<void> _openPicker(
    BuildContext context,
    FormFieldState<T> field,
  ) async {
    if (!enabled) return;
    final selected = await showModalBottomSheet<T>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      showDragHandle: true,
      builder: (_) => _SearchablePickerSheet<T>(
        title: label,
        searchHint: searchHint,
        options: options,
        selected: field.value,
        itemLabel: itemLabel,
        leadingBuilder: leadingBuilder,
      ),
    );
    if (selected != null && context.mounted) {
      field.didChange(selected);
      onChanged(selected);
    }
  }

  @override
  Widget build(BuildContext context) => FormField<T>(
    initialValue: value,
    validator: validator,
    builder: (field) {
      final selected = field.value;
      return InkWell(
        borderRadius: BorderRadius.circular(14),
        onTap: enabled ? () => _openPicker(context, field) : null,
        child: InputDecorator(
          // El placeholder forma parte del contenido; la etiqueta debe
          // permanecer flotante para evitar que ambos textos se superpongan.
          isEmpty: false,
          decoration: InputDecoration(
            labelText: label,
            errorText: field.errorText,
            prefixIcon: prefixIcon == null ? null : Icon(prefixIcon),
            suffixIcon: const Icon(Icons.keyboard_arrow_down_rounded),
            enabled: enabled,
          ),
          child: Row(
            children: [
              if (selected != null && leadingBuilder != null) ...[
                leadingBuilder!(context, selected),
                const SizedBox(width: 10),
              ],
              Expanded(
                child: Text(
                  selected == null ? 'Seleccionar' : itemLabel(selected),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: selected == null
                      ? TextStyle(
                          color: Theme.of(context).colorScheme.onSurfaceVariant,
                        )
                      : null,
                ),
              ),
            ],
          ),
        ),
      );
    },
  );
}

class _SearchablePickerSheet<T> extends StatefulWidget {
  const _SearchablePickerSheet({
    required this.title,
    required this.searchHint,
    required this.options,
    required this.selected,
    required this.itemLabel,
    required this.leadingBuilder,
  });

  final String title;
  final String searchHint;
  final List<T> options;
  final T? selected;
  final String Function(T item) itemLabel;
  final Widget Function(BuildContext context, T item)? leadingBuilder;

  @override
  State<_SearchablePickerSheet<T>> createState() =>
      _SearchablePickerSheetState<T>();
}

class _SearchablePickerSheetState<T> extends State<_SearchablePickerSheet<T>> {
  final search = TextEditingController();
  String query = '';

  @override
  void dispose() {
    search.dispose();
    super.dispose();
  }

  String _normalized(String value) => value
      .toLowerCase()
      .replaceAll(RegExp('[áàäâ]'), 'a')
      .replaceAll(RegExp('[éèëê]'), 'e')
      .replaceAll(RegExp('[íìïî]'), 'i')
      .replaceAll(RegExp('[óòöô]'), 'o')
      .replaceAll(RegExp('[úùüû]'), 'u')
      .replaceAll('ñ', 'n');

  @override
  Widget build(BuildContext context) {
    final normalizedQuery = _normalized(query.trim());
    final filtered = normalizedQuery.isEmpty
        ? widget.options
        : widget.options
              .where(
                (item) => _normalized(
                  widget.itemLabel(item),
                ).contains(normalizedQuery),
              )
              .toList();

    return DraggableScrollableSheet(
      expand: false,
      initialChildSize: .78,
      minChildSize: .45,
      maxChildSize: .94,
      builder: (context, scrollController) => Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 4, 20, 14),
            child: Text(
              'Selecciona ${widget.title.toLowerCase()}',
              style: Theme.of(
                context,
              ).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800),
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: TextField(
              controller: search,
              autofocus: true,
              textInputAction: TextInputAction.search,
              onChanged: (value) => setState(() => query = value),
              decoration: InputDecoration(
                hintText: widget.searchHint,
                prefixIcon: const Icon(Icons.search_rounded),
                suffixIcon: query.isEmpty
                    ? null
                    : IconButton(
                        tooltip: 'Limpiar búsqueda',
                        onPressed: () {
                          search.clear();
                          setState(() => query = '');
                        },
                        icon: const Icon(Icons.close_rounded),
                      ),
              ),
            ),
          ),
          const SizedBox(height: 12),
          Expanded(
            child: filtered.isEmpty
                ? const Center(child: Text('No encontramos resultados.'))
                : ListView.separated(
                    controller: scrollController,
                    keyboardDismissBehavior:
                        ScrollViewKeyboardDismissBehavior.onDrag,
                    padding: const EdgeInsets.fromLTRB(12, 0, 12, 24),
                    itemCount: filtered.length,
                    separatorBuilder: (_, _) => const Divider(height: 1),
                    itemBuilder: (context, index) {
                      final item = filtered[index];
                      final selected = item == widget.selected;
                      return ListTile(
                        leading: widget.leadingBuilder?.call(context, item),
                        title: Text(
                          widget.itemLabel(item),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                        trailing: selected
                            ? Icon(
                                Icons.check_circle_rounded,
                                color: Theme.of(context).colorScheme.primary,
                              )
                            : null,
                        selected: selected,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                        onTap: () => Navigator.pop(context, item),
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }
}
