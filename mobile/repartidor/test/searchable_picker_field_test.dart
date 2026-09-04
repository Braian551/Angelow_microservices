import 'package:angelow_repartidor/ui/core/widgets/searchable_picker_field.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  testWidgets('permite buscar y seleccionar una opción sin desbordar', (
    tester,
  ) async {
    String? selected;
    final options = [
      'Modelo corto',
      'Modelo con un nombre especialmente largo para una pantalla móvil',
    ];

    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: Form(
            child: SearchablePickerField<String>(
              label: 'Modelo',
              searchHint: 'Buscar modelo',
              options: options,
              itemLabel: (item) => item,
              onChanged: (value) => selected = value,
            ),
          ),
        ),
      ),
    );

    expect(
      tester
          .getRect(find.text('Modelo'))
          .overlaps(tester.getRect(find.text('Seleccionar'))),
      isFalse,
    );
    await tester.tap(find.text('Seleccionar'));
    await tester.pumpAndSettle();
    await tester.enterText(
      find.widgetWithText(TextField, 'Buscar modelo'),
      'especialmente',
    );
    await tester.pump();
    await tester.tap(find.text(options.last));
    await tester.pumpAndSettle();

    expect(selected, options.last);
    expect(tester.takeException(), isNull);
  });
}
