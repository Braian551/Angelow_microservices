import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

/// Indicador de espera reutilizable para evitar barras de progreso invasivas.
class AngelowLoadingIndicator extends StatelessWidget {
  const AngelowLoadingIndicator({super.key, this.label, this.compact = false});

  final String? label;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    final indicator = SizedBox(
      width: compact ? 22 : 28,
      height: compact ? 22 : 28,
      child: CircularProgressIndicator(
        strokeWidth: compact ? 2.5 : 3,
        color: AppTheme.primary,
      ),
    );

    if (label == null) {
      return indicator;
    }

    return Container(
      padding: EdgeInsets.symmetric(
        horizontal: compact ? 12 : 18,
        vertical: compact ? 10 : 14,
      ),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFD7E6EE)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          indicator,
          const SizedBox(width: 12),
          Text(
            label!,
            style: TextStyle(
              color: AppTheme.ink,
              fontWeight: compact ? FontWeight.w600 : FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}
