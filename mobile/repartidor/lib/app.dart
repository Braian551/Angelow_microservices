import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'data/repositories/courier_repository.dart';
import 'ui/core/theme/app_theme.dart';
import 'ui/core/widgets/angelow_loading_indicator.dart';
import 'ui/core/widgets/brand_header.dart';
import 'ui/features/auth/view_models/auth_view_model.dart';
import 'ui/features/auth/views/auth_flow_view.dart';
import 'ui/features/courier/views/courier_workspace.dart';

class AngelowCourierApp extends StatelessWidget {
  const AngelowCourierApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        Provider(create: (_) => CourierRepository()),
        ChangeNotifierProvider(create: (_) => AuthViewModel()..initialize()),
      ],
      child: MaterialApp(
        title: 'Angelow Repartidor',
        debugShowCheckedModeBanner: false,
        theme: AppTheme.light,
        home: Consumer<AuthViewModel>(
          builder: (context, auth, _) {
            if (auth.step == AuthStep.loading) {
              return const Scaffold(
                body: Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      BrandHeader(size: 112),
                      SizedBox(height: 24),
                      AngelowLoadingIndicator(label: 'Cargando…'),
                    ],
                  ),
                ),
              );
            }
            if (auth.step != AuthStep.authenticated) {
              return AuthFlowView(viewModel: auth);
            }
            return CourierWorkspace(
              auth: auth,
              repository: context.read<CourierRepository>(),
            );
          },
        ),
      ),
    );
  }
}
